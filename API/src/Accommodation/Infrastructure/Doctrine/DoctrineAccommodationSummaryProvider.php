<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\Doctrine;

use App\Shared\Domain\Port\AccommodationSummary;
use App\Shared\Domain\Port\AccommodationSummaryProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineAccommodationSummaryProvider implements AccommodationSummaryProvider
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function summaryOf(Uuid $accommodationId): ?AccommodationSummary
    {
        $entity = $this->entityManager->find(AccommodationEntity::class, $accommodationId);

        if (null === $entity || null === $entity->getTitle()) {
            return null;
        }

        return new AccommodationSummary(
            accommodationId: $accommodationId,
            title: $entity->getTitle(),
            city: $entity->getCity(),
        );
    }
}
