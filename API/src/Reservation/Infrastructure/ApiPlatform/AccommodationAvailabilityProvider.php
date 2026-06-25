<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Reservation\Application\UseCase\ListAccommodationAvailability;
use App\Reservation\Domain\Entity\DateRange;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<AccommodationAvailabilityOutput>
 */
final readonly class AccommodationAvailabilityProvider implements ProviderInterface
{
    public function __construct(
        private ListAccommodationAvailability $listAvailability,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AccommodationAvailabilityOutput
    {
        $rawId = (string) ($uriVariables['accommodationId'] ?? '');

        $output = new AccommodationAvailabilityOutput();
        $output->accommodationId = $rawId;

        if (!Uuid::isValid($rawId)) {
            return $output;
        }

        $ranges = $this->listAvailability->handle(
            Uuid::fromString($rawId),
            new \DateTimeImmutable('today'),
        );

        $output->busyRanges = array_map(
            static fn (DateRange $range): array => [
                'checkIn' => $range->checkIn()->format('Y-m-d'),
                'checkOut' => $range->checkOut()->format('Y-m-d'),
            ],
            $ranges,
        );

        return $output;
    }
}
