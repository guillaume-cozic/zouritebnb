<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Exception;

final class InvalidReservationException extends \DomainException
{
    public static function becauseAccommodationNotFound(): self
    {
        return new self('Accommodation not found.');
    }

    public static function becauseAccommodationHasNoTeam(): self
    {
        return new self('Accommodation has no owning team.');
    }

    public static function becauseDatesUnavailable(): self
    {
        return new self('These dates are no longer available for this accommodation.');
    }

    public static function becauseHostCannotBookOwnAccommodation(): self
    {
        return new self('A host cannot book an accommodation owned by their own team.');
    }

    public static function becauseGuestCountExceedsCapacity(int $guestCount, int $maxGuests): self
    {
        return new self(\sprintf('Guest count %d exceeds the accommodation capacity of %d.', $guestCount, $maxGuests));
    }

    public static function becauseStayTooShort(int $nights, int $minNights): self
    {
        return new self(\sprintf('Stay of %d night(s) is shorter than the minimum of %d night(s) for this accommodation.', $nights, $minNights));
    }

    public static function becauseStayTooLong(int $nights, int $maxNights): self
    {
        return new self(\sprintf('Stay of %d night(s) is longer than the maximum of %d night(s) for this accommodation.', $nights, $maxNights));
    }

    public static function becauseNegativeTotalPrice(float $totalPrice): self
    {
        return new self(\sprintf('Total price must be greater than or equal to zero, got %s.', $totalPrice));
    }

    public static function becauseNegativePricePerNight(float $pricePerNight): self
    {
        return new self(\sprintf('Price per night must be greater than or equal to zero, got %s.', $pricePerNight));
    }
}
