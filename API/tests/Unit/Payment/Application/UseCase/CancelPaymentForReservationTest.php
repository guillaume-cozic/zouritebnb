<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment\Application\UseCase;

use App\Payment\Application\UseCase\CancelPaymentForReservation;
use App\Payment\Domain\Command\CancelPaymentForReservationCommand;
use App\Payment\Domain\Entity\Payment;
use App\Payment\Domain\Entity\PaymentStatus;
use App\Payment\Domain\Event\PaymentCancelled;
use App\Payment\Domain\Event\PaymentRefunded;
use App\Tests\Unit\Payment\Infrastructure\FakePaymentGateway;
use App\Tests\Unit\Payment\Infrastructure\InMemoryPaymentRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CancelPaymentForReservationTest extends TestCase
{
    private InMemoryPaymentRepository $repository;
    private FakePaymentGateway $gateway;
    private InMemoryEventBus $eventBus;
    private CancelPaymentForReservation $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryPaymentRepository();
        $this->gateway = new FakePaymentGateway();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new CancelPaymentForReservation($this->repository, $this->gateway, $this->eventBus);
    }

    public function test_should_cancel_pending_payment_via_gateway(): void
    {
        $reservationId = Uuid::v7();
        $payment = new Payment(
            id: Uuid::v7(),
            reservationId: $reservationId,
            stripePaymentIntentId: 'pi_test_1',
            status: PaymentStatus::Pending,
            amountCents: 1000,
            currency: 'eur',
            createdAt: new \DateTimeImmutable(),
        );
        $this->repository->save($payment);

        $this->useCase->handle(new CancelPaymentForReservationCommand($reservationId));

        self::assertSame([['type' => 'cancel', 'paymentIntentId' => 'pi_test_1']], $this->gateway->calls);
        self::assertSame(PaymentStatus::Cancelled, $this->repository->findByReservationId($reservationId)->getStatus());
        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PaymentCancelled::class, $events[0]);
    }

    public function test_should_noop_when_no_payment_for_reservation(): void
    {
        $this->useCase->handle(new CancelPaymentForReservationCommand(Uuid::v7()));

        self::assertSame([], $this->gateway->calls);
        self::assertSame([], $this->eventBus->getDispatchedEvents());
    }

    public function test_should_fully_refund_captured_payment(): void
    {
        $reservationId = Uuid::v7();
        $this->repository->save($this->capturedPayment($reservationId, amountCents: 1000));

        $this->useCase->handle(new CancelPaymentForReservationCommand($reservationId));

        self::assertSame([['type' => 'refund', 'paymentIntentId' => 'pi_test_1', 'amountCents' => 1000]], $this->gateway->calls);
        $payment = $this->repository->findByReservationId($reservationId);
        self::assertSame(PaymentStatus::Refunded, $payment->getStatus());
        self::assertSame(1000, $payment->getRefundedAmountCents());
        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PaymentRefunded::class, $events[0]);
        self::assertSame(1000, $events[0]->refundedAmountCents);
    }

    public function test_should_partially_refund_captured_payment_per_policy_percentage(): void
    {
        $reservationId = Uuid::v7();
        $this->repository->save($this->capturedPayment($reservationId, amountCents: 40100));

        $this->useCase->handle(new CancelPaymentForReservationCommand($reservationId, refundPercentage: 50));

        // 50% of 40 100 is 20 050 — the refund is rounded down, never up.
        self::assertSame([['type' => 'refund', 'paymentIntentId' => 'pi_test_1', 'amountCents' => 20050]], $this->gateway->calls);
        $payment = $this->repository->findByReservationId($reservationId);
        self::assertSame(PaymentStatus::Refunded, $payment->getStatus());
        self::assertSame(20050, $payment->getRefundedAmountCents());
    }

    public function test_should_keep_captured_payment_when_policy_grants_no_refund(): void
    {
        $reservationId = Uuid::v7();
        $this->repository->save($this->capturedPayment($reservationId, amountCents: 1000));

        $this->useCase->handle(new CancelPaymentForReservationCommand($reservationId, refundPercentage: 0));

        self::assertSame([], $this->gateway->calls);
        self::assertSame(PaymentStatus::Captured, $this->repository->findByReservationId($reservationId)->getStatus());
        self::assertSame([], $this->eventBus->getDispatchedEvents());
    }

    public function test_should_noop_when_payment_already_refunded(): void
    {
        $reservationId = Uuid::v7();
        $payment = new Payment(
            id: Uuid::v7(),
            reservationId: $reservationId,
            stripePaymentIntentId: 'pi_test_1',
            status: PaymentStatus::Refunded,
            amountCents: 1000,
            currency: 'eur',
            createdAt: new \DateTimeImmutable(),
            refundedAmountCents: 1000,
        );
        $this->repository->save($payment);

        $this->useCase->handle(new CancelPaymentForReservationCommand($reservationId));

        self::assertSame([], $this->gateway->calls);
        self::assertSame([], $this->eventBus->getDispatchedEvents());
    }

    private function capturedPayment(Uuid $reservationId, int $amountCents): Payment
    {
        return new Payment(
            id: Uuid::v7(),
            reservationId: $reservationId,
            stripePaymentIntentId: 'pi_test_1',
            status: PaymentStatus::Captured,
            amountCents: $amountCents,
            currency: 'eur',
            createdAt: new \DateTimeImmutable(),
        );
    }
}
