<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Exception;

final class InvoiceNotAvailableException extends \DomainException
{
    public static function becauseReservationNotConfirmed(string $id): self
    {
        return new self(\sprintf('No invoice is available for reservation "%s": it has not been paid yet.', $id));
    }
}
