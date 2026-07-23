<?php

declare(strict_types=1);

namespace App\Reservation\Application\UseCase;

use App\Reservation\Domain\Command\CreateReservationCommand;
use App\Reservation\Domain\Entity\CancellationPolicy;
use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\GuestCount;
use App\Reservation\Domain\Entity\GuestName;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationPrice;
use App\Reservation\Domain\Exception\InvalidReservationException;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Shared\Domain\Port\AccommodationPricingProvider;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\EventBus;
use App\Shared\Domain\Port\UuidGenerator;
use App\Shared\Domain\Service\StayPriceCalculator;

final readonly class CreateReservation
{
    public function __construct(
        private ReservationRepository $repository,
        private EventBus $eventBus,
        private AccommodationPricingProvider $pricingProvider,
        private StayPriceCalculator $priceCalculator,
        private Clock $clock,
    ) {
    }

    public function handle(CreateReservationCommand $command): string
    {
        $dateRange = new DateRange($command->checkIn, $command->checkOut);

        $pricing = $this->pricingProvider->findByAccommodationId($command->accommodationId);
        if (null === $pricing) {
            throw InvalidReservationException::becauseAccommodationNotFound();
        }

        if ($this->repository->hasOverlappingReservation($command->accommodationId, $dateRange)) {
            throw InvalidReservationException::becauseDatesUnavailable();
        }

        $guestCount = new GuestCount($command->guestCount);
        if (null !== $pricing->maxGuests && $guestCount->value() > $pricing->maxGuests) {
            throw InvalidReservationException::becauseGuestCountExceedsCapacity($guestCount->value(), $pricing->maxGuests);
        }

        $stayPrice = $this->priceCalculator->calculate($pricing, $dateRange->checkIn(), $dateRange->checkOut(), $this->clock->now());

        $price = ReservationPrice::fromStay(
            totalPrice: $stayPrice->totalPrice,
            pricePerNight: $stayPrice->pricePerNight,
            appliedDiscountPercentage: $stayPrice->appliedDiscountPercentage,
            extraServicesTotal: $stayPrice->extraServicesTotal,
        );

        $reservation = Reservation::create(
            id: new ReservationId(UuidGenerator::generate()),
            accommodationId: $command->accommodationId,
            teamId: $command->teamId,
            dateRange: $dateRange,
            guestName: new GuestName($command->guestName),
            guestCount: $guestCount,
            price: $price,
            cancellationPolicy: CancellationPolicy::fromString($pricing->cancellationPolicy),
        );

        $this->repository->save($reservation);
        $this->eventBus->dispatch($reservation->releaseEvents());

        return $reservation->getId()->toString();
    }
}
