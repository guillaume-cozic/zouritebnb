<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\Doctrine;

use App\Shared\Domain\Port\AccommodationPricing;
use App\Shared\Domain\Port\AccommodationPricingProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineAccommodationPricingProvider implements AccommodationPricingProvider
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findByAccommodationId(Uuid $id): ?AccommodationPricing
    {
        $entity = $this->entityManager->find(AccommodationEntity::class, $id);

        if (null === $entity) {
            return null;
        }

        return new AccommodationPricing(
            pricePerNight: (float) $entity->getPrice(),
            weeklyPromotionPercentage: $entity->getWeeklyPromotionPercentage(),
            teamId: $entity->getTeamId(),
            cancellationPolicy: $entity->getCancellationPolicy(),
            maxGuests: $entity->getMaxGuests(),
        );
    }
}
