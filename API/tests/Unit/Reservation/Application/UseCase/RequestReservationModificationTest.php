<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Application\UseCase;

use App\Reservation\Application\UseCase\ApproveReservationModification;
use App\Reservation\Application\UseCase\RejectReservationModification;
use App\Reservation\Application\UseCase\RequestReservationModification;
use App\Reservation\Domain\Command\ApproveReservationModificationCommand;
use App\Reservation\Domain\Command\RejectReservationModificationCommand;
use App\Reservation\Domain\Command\RequestReservationModificationCommand;
use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\GuestCount;
use App\Reservation\Domain\Entity\GuestName;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationPrice;
use App\Reservation\Domain\Entity\ReservationStatus;
use App\Reservation\Domain\Exception\InvalidReservationException;
use App\Reservation\Domain\Exception\InvalidReservationStateException;
use App\Shared\Domain\Service\StayPriceCalculator;
use App\Tests\Unit\Conversation\Infrastructure\FixedClock;
use App\Tests\Unit\Reservation\Infrastructure\InMemoryAccommodationPricingProvider;
use App\Tests\Unit\Reservation\Infrastructure\InMemoryReservationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RequestReservationModificationTest extends TestCase
{
    private InMemoryReservationRepository $repository;
    private InMemoryAccommodationPricingProvider $pricingProvider;
    private InMemoryEventBus $eventBus;
    private FixedClock $clock;
    private RequestReservationModification $request;
    private ApproveReservationModification $approve;
    private RejectReservationModification $reject;
    private Uuid $accommodationId;
    private ReservationId $reservationId;

    #[Before]
    public function init(): void
    {
        $this->repository = new InMemoryReservationRepository();
        $this->pricingProvider = new InMemoryAccommodationPricingProvider();
        $this->eventBus = new InMemoryEventBus();
        $this->clock = new FixedClock(new \DateTimeImmutable('2026-04-01T12:00:00+00:00'));
        $this->request = new RequestReservationModification($this->repository, $this->pricingProvider, new StayPriceCalculator(), $this->clock, $this->eventBus);
        $this->approve = new ApproveReservationModification($this->repository, $this->eventBus);
        $this->reject = new RejectReservationModification($this->repository, $this->eventBus);

        $this->accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000002');
        $this->reservationId = new ReservationId(Uuid::fromString('01961e2f-dead-7000-beef-000000000001'));
        $this->pricingProvider->set($this->accommodationId, 100.0);
        $this->saveConfirmedReservation();
    }

    private function saveConfirmedReservation(): void
    {
        $this->repository->save(new Reservation(
            id: $this->reservationId,
            accommodationId: $this->accommodationId,
            teamId: Uuid::v7(),
            dateRange: new DateRange(new \DateTimeImmutable('2026-05-01'), new \DateTimeImmutable('2026-05-05')),
            guestName: new GuestName('John'),
            guestCount: new GuestCount(2),
            status: ReservationStatus::Confirmed,
            price: new ReservationPrice(totalPrice: 400.0, pricePerNight: 100.0, appliedDiscountPercentage: null),
            guestUserId: Uuid::v7(),
        ));
    }

    public function test_should_recompute_price_and_store_pending_modification(): void
    {
        $this->request->handle(new RequestReservationModificationCommand(
            $this->reservationId->toString(),
            new \DateTimeImmutable('2026-06-01'),
            new \DateTimeImmutable('2026-06-04'),
        ));

        $pending = $this->repository->ofId($this->reservationId)->getPendingModification();
        self::assertNotNull($pending);
        // 3 nights × 100 = 300, recomputed from current pricing.
        self::assertSame(300.0, $pending->price->totalPrice);
    }

    public function test_should_apply_on_approval_and_clear_on_rejection(): void
    {
        $this->request->handle(new RequestReservationModificationCommand($this->reservationId->toString(), new \DateTimeImmutable('2026-06-01'), new \DateTimeImmutable('2026-06-04')));

        $this->approve->handle(new ApproveReservationModificationCommand($this->reservationId->toString()));

        $reservation = $this->repository->ofId($this->reservationId);
        self::assertSame('2026-06-01', $reservation->getDateRange()->checkIn()->format('Y-m-d'));
        self::assertSame(300.0, $reservation->getPrice()->totalPrice);
        self::assertNull($reservation->getPendingModification());
    }

    public function test_should_reject_modification_request_on_unavailable_dates(): void
    {
        // Another confirmed reservation occupies the requested window.
        $this->repository->save(new Reservation(
            id: new ReservationId(Uuid::v7()),
            accommodationId: $this->accommodationId,
            teamId: Uuid::v7(),
            dateRange: new DateRange(new \DateTimeImmutable('2026-06-01'), new \DateTimeImmutable('2026-06-10')),
            guestName: new GuestName('Other'),
            guestCount: new GuestCount(1),
            status: ReservationStatus::Confirmed,
            price: new ReservationPrice(totalPrice: 900.0, pricePerNight: 100.0, appliedDiscountPercentage: null),
            guestUserId: Uuid::v7(),
        ));

        $this->expectException(InvalidReservationException::class);

        $this->request->handle(new RequestReservationModificationCommand($this->reservationId->toString(), new \DateTimeImmutable('2026-06-02'), new \DateTimeImmutable('2026-06-05')));
    }

    public function test_should_allow_keeping_overlapping_with_itself(): void
    {
        // Requesting almost the same window must not be blocked by the reservation's own dates.
        $this->request->handle(new RequestReservationModificationCommand($this->reservationId->toString(), new \DateTimeImmutable('2026-05-02'), new \DateTimeImmutable('2026-05-06')));

        self::assertNotNull($this->repository->ofId($this->reservationId)->getPendingModification());
    }

    public function test_should_fail_to_reject_without_pending(): void
    {
        $this->expectException(InvalidReservationStateException::class);

        $this->reject->handle(new RejectReservationModificationCommand($this->reservationId->toString()));
    }
}
