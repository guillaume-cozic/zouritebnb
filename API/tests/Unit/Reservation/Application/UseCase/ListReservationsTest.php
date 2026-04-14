<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Application\UseCase;

use App\Reservation\Application\UseCase\ListReservations;
use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\GuestName;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\ReservationId;
use App\Tests\Unit\Reservation\Infrastructure\InMemoryReservationRepository;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ListReservationsTest extends TestCase
{
    private InMemoryReservationRepository $repository;
    private ListReservations $useCase;

    private Uuid $teamA;
    private Uuid $teamB;
    private Uuid $accommodation1;
    private Uuid $accommodation2;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryReservationRepository();
        $this->useCase = new ListReservations($this->repository);

        $this->teamA = Uuid::fromString('01961e2f-dead-7000-beef-0000000000a1');
        $this->teamB = Uuid::fromString('01961e2f-dead-7000-beef-0000000000a2');
        $this->accommodation1 = Uuid::fromString('01961e2f-dead-7000-beef-0000000000c1');
        $this->accommodation2 = Uuid::fromString('01961e2f-dead-7000-beef-0000000000c2');

        $this->saveReservation('01961e2f-dead-7000-beef-000000000001', $this->teamA, $this->accommodation1, '2026-05-01', '2026-05-05');
        $this->saveReservation('01961e2f-dead-7000-beef-000000000002', $this->teamA, $this->accommodation2, '2026-06-01', '2026-06-10');
        $this->saveReservation('01961e2f-dead-7000-beef-000000000003', $this->teamA, $this->accommodation1, '2026-07-01', '2026-07-05');
        $this->saveReservation('01961e2f-dead-7000-beef-000000000004', $this->teamB, $this->accommodation1, '2026-05-01', '2026-05-05');
    }

    private function saveReservation(string $idStr, Uuid $teamId, Uuid $accommodationId, string $in, string $out): void
    {
        $reservation = Reservation::create(
            id: new ReservationId(Uuid::fromString($idStr)),
            accommodationId: $accommodationId,
            teamId: $teamId,
            dateRange: new DateRange(new \DateTimeImmutable($in), new \DateTimeImmutable($out)),
            guestName: new GuestName('Guest'),
            price: new \App\Reservation\Domain\Entity\ReservationPrice(totalPrice: 400.0, pricePerNight: 100.0, appliedDiscountPercentage: null),
        );
        $reservation->releaseEvents();
        $this->repository->save($reservation);
    }

    public function testShouldScopeResultsByTeamId(): void
    {
        $results = $this->useCase->handle($this->teamA);
        self::assertCount(3, $results);

        $resultsB = $this->useCase->handle($this->teamB);
        self::assertCount(1, $resultsB);
    }

    public function testShouldFilterByAccommodationId(): void
    {
        $results = $this->useCase->handle($this->teamA, $this->accommodation1);
        self::assertCount(2, $results);
    }

    public function testShouldFilterByDateRange(): void
    {
        $results = $this->useCase->handle(
            $this->teamA,
            null,
            new \DateTimeImmutable('2026-05-15'),
            new \DateTimeImmutable('2026-06-15'),
        );
        self::assertCount(1, $results);
    }
}
