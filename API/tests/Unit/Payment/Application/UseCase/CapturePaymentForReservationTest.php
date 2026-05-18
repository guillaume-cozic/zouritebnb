<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment\Application\UseCase;

use App\Payment\Application\UseCase\CapturePaymentForReservation;
use App\Payment\Domain\Command\CapturePaymentForReservationCommand;
use App\Payment\Domain\Entity\Payment;
use App\Payment\Domain\Entity\PaymentStatus;
use App\Payment\Domain\Event\PaymentCaptured;
use App\Payment\Domain\Exception\PaymentNotFoundException;
use App\Tests\Unit\Payment\Infrastructure\FakePaymentGateway;
use App\Tests\Unit\Payment\Infrastructure\InMemoryPaymentRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CapturePaymentForReservationTest extends TestCase
{
    private InMemoryPaymentRepository $repository;
    private FakePaymentGateway $gateway;
    private InMemoryEventBus $eventBus;
    private CapturePaymentForReservation $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryPaymentRepository();
        $this->gateway = new FakePaymentGateway();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new CapturePaymentForReservation($this->repository, $this->gateway, $this->eventBus);
    }

    public function testShouldCapturePaymentLinkedToReservation(): void
    {
        $reservationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');
        $payment = new Payment(
            id: Uuid::fromString('01961e2f-dead-7000-beef-000000000001'),
            reservationId: $reservationId,
            stripePaymentIntentId: 'pi_test_1',
            status: PaymentStatus::Authorized,
            amountCents: 1000,
            currency: 'eur',
            createdAt: new \DateTimeImmutable('2026-05-17T10:00:00+00:00'),
        );
        $this->repository->save($payment);

        $this->useCase->handle(new CapturePaymentForReservationCommand($reservationId));

        self::assertSame([['type' => 'capture', 'paymentIntentId' => 'pi_test_1']], $this->gateway->calls);
        $updated = $this->repository->findByReservationId($reservationId);
        self::assertNotNull($updated);
        self::assertSame(PaymentStatus::Captured, $updated->getStatus());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PaymentCaptured::class, $events[0]);
    }

    public function testShouldThrowWhenNoPaymentForReservation(): void
    {
        $this->expectException(PaymentNotFoundException::class);

        $this->useCase->handle(new CapturePaymentForReservationCommand(Uuid::v7()));
    }
}
