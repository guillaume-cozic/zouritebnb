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
    private Uuid $noGuest;
    private Uuid $guestUser;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryReservationRepository();
        $this->useCase = new ListReservations($this->repository);

        $this->teamA = Uuid::fromString('01961e2f-dead-7000-beef-0000000000a1');
        $this->teamB = Uuid::fromString('01961e2f-dead-7000-beef-0000000000a2');
        $this->accommodation1 = Uuid::fromString('01961e2f-dead-7000-beef-0000000000c1');
        $this->accommodation2 = Uuid::fromString('01961e2f-dead-7000-beef-0000000000c2');
        // A guest id that matches no reservation, so listing is scoped to the team only.
        $this->noGuest = Uuid::fromString('01961e2f-dead-7000-beef-0000000000f0');
        $this->guestUser = Uuid::fromString('01961e2f-dead-7000-beef-0000000000f1');

        $this->saveReservation('01961e2f-dead-7000-beef-000000000001', $this->teamA, $this->accommodation1, '2026-05-01', '2026-05-05');
        $this->saveReservation('01961e2f-dead-7000-beef-000000000002', $this->teamA, $this->accommodation2, '2026-06-01', '2026-06-10');
        $this->saveReservation('01961e2f-dead-7000-beef-000000000003', $this->teamA, $this->accommodation1, '2026-07-01', '2026-07-05');
        $this->saveReservation('01961e2f-dead-7000-beef-000000000004', $this->teamB, $this->accommodation1, '2026-05-01', '2026-05-05');
    }

    private function saveReservation(string $idStr, Uuid $teamId, Uuid $accommodationId, string $in, string $out, ?Uuid $guestUserId = null): void
    {
        if (null === $guestUserId) {
            $reservation = Reservation::create(
                id: new ReservationId(Uuid::fromString($idStr)),
                accommodationId: $accommodationId,
                teamId: $teamId,
                dateRange: new DateRange(new \DateTimeImmutable($in), new \DateTimeImmutable($out)),
                guestName: new GuestName('Guest'),
                price: new \App\Reservation\Domain\Entity\ReservationPrice(totalPrice: 400.0, pricePerNight: 100.0, appliedDiscountPercentage: null),
            );
        } else {
            $reservation = Reservation::request(
                id: new ReservationId(Uuid::fromString($idStr)),
                accommodationId: $accommodationId,
                teamId: $teamId,
                dateRange: new DateRange(new \DateTimeImmutable($in), new \DateTimeImmutable($out)),
                guestName: new GuestName('Guest'),
                price: new \App\Reservation\Domain\Entity\ReservationPrice(totalPrice: 400.0, pricePerNight: 100.0, appliedDiscountPercentage: null),
                guestUserId: $guestUserId,
            );
        }
        $reservation->releaseEvents();
        $this->repository->save($reservation);
    }

    public function test_should_scope_results_by_team_id(): void
    {
        $results = $this->useCase->handle($this->teamA, $this->noGuest);
        self::assertCount(3, $results);

        $resultsB = $this->useCase->handle($this->teamB, $this->noGuest);
        self::assertCount(1, $resultsB);
    }

    public function test_should_also_return_reservations_where_user_is_guest(): void
    {
        // A reservation in teamB where the current user (teamA) is the guest.
        $this->saveReservation('01961e2f-dead-7000-beef-000000000005', $this->teamB, $this->accommodation2, '2026-08-01', '2026-08-05', $this->guestUser);

        $results = $this->useCase->handle($this->teamA, $this->guestUser);

        // 3 reservations as host (teamA) + 1 as guest in teamB.
        self::assertCount(4, $results);
    }

    public function test_should_filter_by_accommodation_id(): void
    {
        $results = $this->useCase->handle($this->teamA, $this->noGuest, $this->accommodation1);
        self::assertCount(2, $results);
    }

    public function test_should_filter_by_date_range(): void
    {
        $results = $this->useCase->handle(
            $this->teamA,
            $this->noGuest,
            null,
            new \DateTimeImmutable('2026-05-15'),
            new \DateTimeImmutable('2026-06-15'),
        );
        self::assertCount(1, $results);
    }
}
