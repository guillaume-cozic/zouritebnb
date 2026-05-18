<?php

declare(strict_types=1);

namespace App\Payment\Domain\Exception;

final class PaymentNotFoundException extends \DomainException
{
    public static function becauseId(string $paymentId): self
    {
        return new self(\sprintf('Payment "%s" not found.', $paymentId));
    }

    public static function becausePaymentIntentId(string $paymentIntentId): self
    {
        return new self(\sprintf('Payment with intent id "%s" not found.', $paymentIntentId));
    }

    public static function becauseReservationId(string $reservationId): self
    {
        return new self(\sprintf('Payment for reservation "%s" not found.', $reservationId));
    }
}
