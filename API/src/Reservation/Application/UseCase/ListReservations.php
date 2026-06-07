<?php

declare(strict_types=1);

namespace App\Reservation\Application\UseCase;

use App\Reservation\Domain\Port\ReservationRepository;
use Symfony\Component\Uid\Uuid;

final readonly class ListReservations
{
    public function __construct(
        private ReservationRepository $repository,
    ) {
    }

    /**
     * @return \App\Reservation\Domain\Entity\Reservation[]
     */
    public function handle(
        Uuid $teamId,
        Uuid $guestUserId,
        ?Uuid $accommodationId = null,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
    ): array {
        return $this->repository->list($teamId, $guestUserId, $accommodationId, $from, $to);
    }
}
