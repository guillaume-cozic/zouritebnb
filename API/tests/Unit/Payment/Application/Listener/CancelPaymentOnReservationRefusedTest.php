<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment\Application\Listener;

use App\Payment\Application\Listener\CancelPaymentOnReservationRefused;
use App\Payment\Application\UseCase\CancelPaymentForReservation;
use App\Payment\Domain\Entity\Payment;
use App\Payment\Domain\Entity\PaymentStatus;
use App\Payment\Domain\Event\PaymentCancelled;
use App\Shared\Domain\Event\ReservationRefused;
use App\Tests\Unit\Payment\Infrastructure\FakePaymentGateway;
use App\Tests\Unit\Payment\Infrastructure\InMemoryPaymentRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CancelPaymentOnReservationRefusedTest extends TestCase
{
    private InMemoryPaymentRepository $repository;
    private FakePaymentGateway $gateway;
    private InMemoryEventBus $eventBus;
    private CancelPaymentOnReservationRefused $listener;

    #[Before]
    public function initListener(): void
    {
        $this->repository = new InMemoryPaymentRepository();
        $this->gateway = new FakePaymentGateway();
        $this->eventBus = new InMemoryEventBus();
        $this->listener = new CancelPaymentOnReservationRefused(
            new CancelPaymentForReservation($this->repository, $this->gateway, $this->eventBus),
        );
    }

    #[DataProvider('automaticFlagProvider')]
    public function test_should_cancel_payment_when_reservation_refused(bool $isAutomatic): void
    {
        $reservationId = Uuid::v7();
        $payment = new Payment(
            id: Uuid::v7(),
            reservationId: $reservationId,
            stripePaymentIntentId: 'pi_test_1',
            status: PaymentStatus::Authorized,
            amountCents: 1000,
            currency: 'eur',
            createdAt: new \DateTimeImmutable(),
        );
        $this->repository->save($payment);

        ($this->listener)(new ReservationRefused($reservationId, $isAutomatic));

        self::assertSame([['type' => 'cancel', 'paymentIntentId' => 'pi_test_1']], $this->gateway->calls);
        self::assertSame(PaymentStatus::Cancelled, $this->repository->findByReservationId($reservationId)->getStatus());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PaymentCancelled::class, $events[0]);
    }

    public static function automaticFlagProvider(): \Generator
    {
        yield 'manual refusal' => [false];
        yield 'automatic refusal' => [true];
    }

    public function test_should_noop_when_no_payment_for_reservation(): void
    {
        ($this->listener)(new ReservationRefused(Uuid::v7()));

        self::assertSame([], $this->gateway->calls);
        self::assertSame([], $this->eventBus->getDispatchedEvents());
    }
}
