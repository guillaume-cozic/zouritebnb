<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Reservation\Application\UseCase\ListReservations;
use App\Shared\Infrastructure\Security\CurrentUser;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<ReservationOutput>
 */
final readonly class ReservationCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ListReservations $listReservations,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * @return ReservationOutput[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $filters = $context['filters'] ?? [];

        $accommodationId = null;
        if (!empty($filters['accommodationId']) && Uuid::isValid((string) $filters['accommodationId'])) {
            $accommodationId = Uuid::fromString((string) $filters['accommodationId']);
        }

        $from = null;
        if (!empty($filters['from'])) {
            try {
                $from = new \DateTimeImmutable((string) $filters['from']);
            } catch (\Exception) {
                $from = null;
            }
        }

        $to = null;
        if (!empty($filters['to'])) {
            try {
                $to = new \DateTimeImmutable((string) $filters['to']);
            } catch (\Exception) {
                $to = null;
            }
        }

        $reservations = $this->listReservations->handle(
            teamId: $this->currentUser->teamId(),
            guestUserId: $this->currentUser->id(),
            accommodationId: $accommodationId,
            from: $from,
            to: $to,
        );

        return array_map(
            static fn ($reservation) => ReservationOutput::fromEntity($reservation),
            $reservations,
        );
    }
}
