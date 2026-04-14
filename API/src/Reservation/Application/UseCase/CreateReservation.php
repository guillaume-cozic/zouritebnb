<?php

declare(strict_types=1);

namespace App\Reservation\Application\UseCase;

use App\Reservation\Domain\Command\CreateReservationCommand;
use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\GuestName;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationPrice;
use App\Reservation\Domain\Exception\InvalidReservationException;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Shared\Domain\Port\AccommodationPricingProvider;
use App\Shared\Domain\Port\EventBus;
use App\Shared\Domain\Port\UuidGenerator;

final readonly class CreateReservation
{
    private const int WEEKLY_PROMOTION_MIN_NIGHTS = 7;

    public function __construct(
        private ReservationRepository $repository,
        private EventBus $eventBus,
        private AccommodationPricingProvider $pricingProvider,
    ) {
    }

    public function handle(CreateReservationCommand $command): string
    {
        $dateRange = new DateRange($command->checkIn, $command->checkOut);

        $pricing = $this->pricingProvider->findByAccommodationId($command->accommodationId);
        if (null === $pricing) {
            throw InvalidReservationException::becauseAccommodationNotFound();
        }

        $checkInDay = $dateRange->checkIn()->setTime(0, 0);
        $checkOutDay = $dateRange->checkOut()->setTime(0, 0);
        $nights = (int) $checkInDay->diff($checkOutDay)->days;

        if ($nights >= self::WEEKLY_PROMOTION_MIN_NIGHTS && null !== $pricing->weeklyPromotionPercentage) {
            $discountedPricePerNight = $pricing->pricePerNight * (1 - $pricing->weeklyPromotionPercentage / 100);
            $appliedDiscount = $pricing->weeklyPromotionPercentage;
        } else {
            $discountedPricePerNight = $pricing->pricePerNight;
            $appliedDiscount = null;
        }

        $price = new ReservationPrice(
            totalPrice: $discountedPricePerNight * $nights,
            pricePerNight: $pricing->pricePerNight,
            appliedDiscountPercentage: $appliedDiscount,
        );

        $reservation = Reservation::create(
            id: new ReservationId(UuidGenerator::generate()),
            accommodationId: $command->accommodationId,
            teamId: $command->teamId,
            dateRange: $dateRange,
            guestName: new GuestName($command->guestName),
            price: $price,
        );

        $this->repository->save($reservation);
        $this->eventBus->dispatch($reservation->releaseEvents());

        return $reservation->getId()->toString();
    }
}
