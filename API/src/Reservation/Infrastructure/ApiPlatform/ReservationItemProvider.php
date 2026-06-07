<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Reservation\Infrastructure\Security\ReservationAccessGuard;
use App\Shared\Infrastructure\Security\CurrentUser;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<ReservationOutput>
 */
final readonly class ReservationItemProvider implements ProviderInterface
{
    public function __construct(
        private ReservationRepository $repository,
        private CurrentUser $currentUser,
        private ReservationAccessGuard $accessGuard,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ReservationOutput
    {
        $idString = (string) $uriVariables['id'];

        if (!Uuid::isValid($idString)) {
            return null;
        }

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($idString)));

        if (null === $reservation) {
            return null;
        }

        $this->accessGuard->assertHostOrGuest($reservation, $this->currentUser);

        return ReservationOutput::fromEntity($reservation);
    }
}
