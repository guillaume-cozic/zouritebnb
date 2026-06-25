<?php

declare(strict_types=1);

namespace App\Reservation\Application\UseCase;

use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Port\ReservationRepository;
use Symfony\Component\Uid\Uuid;

final readonly class ListAccommodationAvailability
{
    public function __construct(
        private ReservationRepository $repository,
    ) {
    }

    /**
     * Returns the date ranges that block availability for an accommodation
     * (pending or confirmed reservations whose stay is not over yet).
     *
     * @return DateRange[]
     */
    public function handle(Uuid $accommodationId, \DateTimeImmutable $from): array
    {
        return $this->repository->busyRanges($accommodationId, $from);
    }
}
