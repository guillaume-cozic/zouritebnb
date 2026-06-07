<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment\Application\Listener;

use App\Payment\Application\Listener\CapturePaymentOnReservationConfirmed;
use App\Payment\Application\UseCase\CapturePaymentForReservation;
use App\Payment\Domain\Entity\Payment;
use App\Payment\Domain\Entity\PaymentStatus;
use App\Payment\Domain\Event\PaymentCaptured;
use App\Shared\Domain\Event\ReservationConfirmed;
use App\Tests\Unit\Payment\Infrastructure\FakePaymentGateway;
use App\Tests\Unit\Payment\Infrastructure\InMemoryPaymentRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CapturePaymentOnReservationConfirmedTest extends TestCase
{
    private InMemoryPaymentRepository $repository;
    private FakePaymentGateway $gateway;
    private InMemoryEventBus $eventBus;
    private CapturePaymentOnReservationConfirmed $listener;

    #[Before]
    public function initListener(): void
    {
        $this->repository = new InMemoryPaymentRepository();
        $this->gateway = new FakePaymentGateway();
        $this->eventBus = new InMemoryEventBus();
        $this->listener = new CapturePaymentOnReservationConfirmed(
            new CapturePaymentForReservation($this->repository, $this->gateway, $this->eventBus),
        );
    }

    public function test_should_capture_payment_when_reservation_confirmed(): void
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

        ($this->listener)(new ReservationConfirmed($reservationId));

        self::assertSame([['type' => 'capture', 'paymentIntentId' => 'pi_test_1']], $this->gateway->calls);
        self::assertSame(PaymentStatus::Captured, $this->repository->findByReservationId($reservationId)->getStatus());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PaymentCaptured::class, $events[0]);
    }

    public function test_should_swallow_payment_not_found_exception_when_no_payment_for_reservation(): void
    {
        ($this->listener)(new ReservationConfirmed(Uuid::v7()));

        self::assertSame([], $this->gateway->calls);
        self::assertSame([], $this->eventBus->getDispatchedEvents());
    }
}
