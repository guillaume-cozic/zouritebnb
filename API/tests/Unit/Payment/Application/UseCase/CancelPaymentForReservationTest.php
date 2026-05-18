<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment\Application\UseCase;

use App\Payment\Application\UseCase\CancelPaymentForReservation;
use App\Payment\Domain\Command\CancelPaymentForReservationCommand;
use App\Payment\Domain\Entity\Payment;
use App\Payment\Domain\Entity\PaymentStatus;
use App\Payment\Domain\Event\PaymentCancelled;
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

    public function testShouldCancelPendingPaymentViaGateway(): void
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

    public function testShouldNoopWhenNoPaymentForReservation(): void
    {
        $this->useCase->handle(new CancelPaymentForReservationCommand(Uuid::v7()));

        self::assertSame([], $this->gateway->calls);
        self::assertSame([], $this->eventBus->getDispatchedEvents());
    }

    public function testShouldNoopWhenPaymentAlreadyCaptured(): void
    {
        $reservationId = Uuid::v7();
        $payment = new Payment(
            id: Uuid::v7(),
            reservationId: $reservationId,
            stripePaymentIntentId: 'pi_test_1',
            status: PaymentStatus::Captured,
            amountCents: 1000,
            currency: 'eur',
            createdAt: new \DateTimeImmutable(),
        );
        $this->repository->save($payment);

        $this->useCase->handle(new CancelPaymentForReservationCommand($reservationId));

        self::assertSame([], $this->gateway->calls);
        self::assertSame(PaymentStatus::Captured, $this->repository->findByReservationId($reservationId)->getStatus());
    }
}
