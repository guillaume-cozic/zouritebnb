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
            instantBooking: $entity->isInstantBooking(),
            minNights: $entity->getMinNights(),
            maxNights: $entity->getMaxNights(),
            weekendSurchargePercentage: $entity->getWeekendSurchargePercentage(),
            lastMinuteDiscountPercentage: $entity->getLastMinuteDiscountPercentage(),
            lastMinuteDays: $entity->getLastMinuteDays(),
            pricePeriods: $entity->getPricePeriods() ?? [],
            billedExtraServices: $this->billedExtraServices($entity->getExtraServices() ?? []),
        );
    }

    /**
     * Keeps only the extra services billed with the reservation (a missing
     * "billedWithReservation" key means the service is paid on site).
     *
     * @param array<array{name: string, price: float, billedWithReservation?: bool}> $extraServices
     *
     * @return array<array{name: string, price: float}>
     */
    private function billedExtraServices(array $extraServices): array
    {
        $billed = [];
        foreach ($extraServices as $service) {
            if (true === ($service['billedWithReservation'] ?? false)) {
                $billed[] = ['name' => (string) $service['name'], 'price' => (float) $service['price']];
            }
        }

        return $billed;
    }
}
