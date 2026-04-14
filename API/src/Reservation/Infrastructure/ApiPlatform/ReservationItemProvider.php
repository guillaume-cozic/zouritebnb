<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Port\ReservationRepository;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<ReservationOutput>
 */
final readonly class ReservationItemProvider implements ProviderInterface
{
    public function __construct(
        private ReservationRepository $repository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ReservationOutput
    {
        $idString = (string) $uriVariables['id'];

        if (!Uuid::isValid($idString)) {
            return null;
        }

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($idString)));

        return null === $reservation ? null : ReservationOutput::fromEntity($reservation);
    }
}
