<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment\Application\Listener;

use App\Payment\Application\Listener\LinkPaymentOnReservationRequested;
use App\Payment\Application\UseCase\LinkPaymentToReservation;
use App\Payment\Domain\Entity\Payment;
use App\Payment\Domain\Entity\PaymentStatus;
use App\Payment\Domain\Event\PaymentLinkedToReservation;
use App\Shared\Domain\Event\ReservationRequested;
use App\Tests\Unit\Payment\Infrastructure\InMemoryPaymentRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class LinkPaymentOnReservationRequestedTest extends TestCase
{
    private InMemoryPaymentRepository $repository;
    private InMemoryEventBus $eventBus;
    private LinkPaymentOnReservationRequested $listener;

    #[Before]
    public function initListener(): void
    {
        $this->repository = new InMemoryPaymentRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->listener = new LinkPaymentOnReservationRequested(
            new LinkPaymentToReservation($this->repository, $this->eventBus),
        );
    }

    public function test_should_link_payment_to_reservation_when_event_carries_payment_intent_id(): void
    {
        $payment = $this->savePayment('pi_test_1', null);
        $reservationId = Uuid::v7();

        ($this->listener)(new ReservationRequested(
            reservationId: $reservationId,
            guestUserId: Uuid::v7(),
            note: null,
            paymentIntentId: 'pi_test_1',
        ));

        $stored = $this->repository->findByPaymentIntentId('pi_test_1');
        self::assertNotNull($stored);
        self::assertTrue($stored->getReservationId()?->equals($reservationId) ?? false);

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PaymentLinkedToReservation::class, $events[0]);
    }

    #[DataProvider('emptyPaymentIntentIdProvider')]
    public function test_should_noop_when_event_has_no_payment_intent_id(?string $paymentIntentId): void
    {
        ($this->listener)(new ReservationRequested(
            reservationId: Uuid::v7(),
            guestUserId: Uuid::v7(),
            note: null,
            paymentIntentId: $paymentIntentId,
        ));

        self::assertSame([], $this->eventBus->getDispatchedEvents());
    }

    public static function emptyPaymentIntentIdProvider(): \Generator
    {
        yield 'null payment intent id' => [null];
        yield 'empty payment intent id' => [''];
    }

    public function test_should_swallow_payment_not_found_exception_when_payment_row_missing(): void
    {
        ($this->listener)(new ReservationRequested(
            reservationId: Uuid::v7(),
            guestUserId: Uuid::v7(),
            note: null,
            paymentIntentId: 'pi_test_missing',
        ));

        self::assertSame([], $this->eventBus->getDispatchedEvents());
    }

    private function savePayment(string $paymentIntentId, ?Uuid $reservationId): Payment
    {
        $payment = new Payment(
            id: Uuid::v7(),
            reservationId: $reservationId,
            stripePaymentIntentId: $paymentIntentId,
            status: PaymentStatus::Pending,
            amountCents: 1000,
            currency: 'eur',
            createdAt: new \DateTimeImmutable(),
        );
        $this->repository->save($payment);

        return $payment;
    }
}
