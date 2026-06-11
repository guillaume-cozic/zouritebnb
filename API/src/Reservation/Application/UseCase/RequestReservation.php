<?php

declare(strict_types=1);

namespace App\Reservation\Application\UseCase;

use App\Reservation\Domain\Command\RequestReservationCommand;
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
use App\Shared\Domain\Service\StayPriceCalculator;

final readonly class RequestReservation
{
    public function __construct(
        private ReservationRepository $repository,
        private EventBus $eventBus,
        private AccommodationPricingProvider $pricingProvider,
        private StayPriceCalculator $priceCalculator,
    ) {
    }

    public function handle(RequestReservationCommand $command): string
    {
        $dateRange = new DateRange($command->checkIn, $command->checkOut);

        $pricing = $this->pricingProvider->findByAccommodationId($command->accommodationId);
        if (null === $pricing) {
            throw InvalidReservationException::becauseAccommodationNotFound();
        }
        if (null === $pricing->teamId) {
            throw InvalidReservationException::becauseAccommodationHasNoTeam();
        }

        $stayPrice = $this->priceCalculator->calculate($pricing, $dateRange->checkIn(), $dateRange->checkOut());

        $price = new ReservationPrice(
            totalPrice: $stayPrice->totalPrice,
            pricePerNight: $stayPrice->pricePerNight,
            appliedDiscountPercentage: $stayPrice->appliedDiscountPercentage,
        );

        $reservation = Reservation::request(
            id: new ReservationId(UuidGenerator::generate()),
            accommodationId: $command->accommodationId,
            teamId: $pricing->teamId,
            dateRange: $dateRange,
            guestName: new GuestName($command->guestName),
            price: $price,
            guestUserId: $command->guestUserId,
            note: $command->note,
            paymentIntentId: $command->paymentIntentId,
        );

        $this->repository->save($reservation);
        $this->eventBus->dispatch($reservation->releaseEvents());

        return $reservation->getId()->toString();
    }
}
