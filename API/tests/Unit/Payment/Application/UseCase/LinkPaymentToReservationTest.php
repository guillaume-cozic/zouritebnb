<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment\Application\UseCase;

use App\Payment\Application\UseCase\LinkPaymentToReservation;
use App\Payment\Domain\Command\LinkPaymentToReservationCommand;
use App\Payment\Domain\Entity\Payment;
use App\Payment\Domain\Entity\PaymentStatus;
use App\Payment\Domain\Event\PaymentLinkedToReservation;
use App\Payment\Domain\Exception\PaymentNotFoundException;
use App\Tests\Unit\Payment\Infrastructure\InMemoryPaymentRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class LinkPaymentToReservationTest extends TestCase
{
    private InMemoryPaymentRepository $repository;
    private InMemoryEventBus $eventBus;
    private LinkPaymentToReservation $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryPaymentRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new LinkPaymentToReservation($this->repository, $this->eventBus);
    }

    public function test_should_link_reservation_id_to_existing_payment(): void
    {
        $payment = new Payment(
            id: Uuid::v7(),
            reservationId: null,
            stripePaymentIntentId: 'pi_test_1',
            status: PaymentStatus::Pending,
            amountCents: 1000,
            currency: 'eur',
            createdAt: new \DateTimeImmutable(),
        );
        $this->repository->save($payment);

        $reservationId = Uuid::v7();
        $this->useCase->handle(new LinkPaymentToReservationCommand('pi_test_1', $reservationId));

        $stored = $this->repository->findByPaymentIntentId('pi_test_1');
        self::assertNotNull($stored);
        self::assertTrue($stored->getReservationId()?->equals($reservationId) ?? false);

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PaymentLinkedToReservation::class, $events[0]);
    }

    public function test_should_throw_when_payment_intent_id_unknown(): void
    {
        $this->expectException(PaymentNotFoundException::class);
        $this->useCase->handle(new LinkPaymentToReservationCommand('pi_test_missing', Uuid::v7()));
    }
}
