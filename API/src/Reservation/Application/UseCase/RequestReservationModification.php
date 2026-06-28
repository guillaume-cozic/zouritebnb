<?php

declare(strict_types=1);

namespace App\Reservation\Application\UseCase;

use App\Reservation\Domain\Command\RequestReservationModificationCommand;
use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationPrice;
use App\Reservation\Domain\Exception\InvalidReservationException;
use App\Reservation\Domain\Exception\ReservationNotFoundException;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Shared\Domain\Port\AccommodationPricingProvider;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\EventBus;
use App\Shared\Domain\Service\StayPriceCalculator;
use Symfony\Component\Uid\Uuid;

final readonly class RequestReservationModification
{
    public function __construct(
        private ReservationRepository $repository,
        private AccommodationPricingProvider $pricingProvider,
        private StayPriceCalculator $priceCalculator,
        private Clock $clock,
        private EventBus $eventBus,
    ) {
    }

    public function handle(RequestReservationModificationCommand $command): void
    {
        $id = new ReservationId(Uuid::fromString($command->reservationId));
        $reservation = $this->repository->ofId($id);
        if (null === $reservation) {
            throw ReservationNotFoundException::becauseId($command->reservationId);
        }

        $dateRange = new DateRange($command->checkIn, $command->checkOut);

        $pricing = $this->pricingProvider->findByAccommodationId($reservation->getAccommodationId());
        if (null === $pricing) {
            throw InvalidReservationException::becauseAccommodationNotFound();
        }

        if ($this->repository->hasOverlappingReservation($reservation->getAccommodationId(), $dateRange, $id)) {
            throw InvalidReservationException::becauseDatesUnavailable();
        }

        $nights = $dateRange->nights();
        if (null !== $pricing->minNights && $nights < $pricing->minNights) {
            throw InvalidReservationException::becauseStayTooShort($nights, $pricing->minNights);
        }
        if (null !== $pricing->maxNights && $nights > $pricing->maxNights) {
            throw InvalidReservationException::becauseStayTooLong($nights, $pricing->maxNights);
        }

        $stayPrice = $this->priceCalculator->calculate($pricing, $dateRange->checkIn(), $dateRange->checkOut(), $this->clock->now());
        $price = ReservationPrice::fromStay(
            totalPrice: $stayPrice->totalPrice,
            pricePerNight: $stayPrice->pricePerNight,
            appliedDiscountPercentage: $stayPrice->appliedDiscountPercentage,
        );

        $reservation->requestModification($dateRange, $price, $this->clock->now());

        $this->repository->save($reservation);
        $this->eventBus->dispatch($reservation->releaseEvents());
    }
}
