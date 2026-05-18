<?php

declare(strict_types=1);

namespace App\Payment\Domain\Exception;

use App\Payment\Domain\Entity\PaymentStatus;

final class InvalidPaymentException extends \DomainException
{
    public static function becauseAmountIsNotPositive(int $amountCents): self
    {
        return new self(\sprintf('Payment amount must be greater than zero, got %d cents.', $amountCents));
    }

    public static function becauseCurrencyIsInvalid(string $currency): self
    {
        return new self(\sprintf('Payment currency must be a 3-letter ISO code, got "%s".', $currency));
    }

    public static function becausePaymentIntentIdIsBlank(): self
    {
        return new self('Payment intent identifier must not be blank.');
    }

    public static function becauseTransitionIsInvalid(PaymentStatus $from, PaymentStatus $to): self
    {
        return new self(\sprintf(
            'Cannot transition payment from "%s" to "%s".',
            $from->value,
            $to->value,
        ));
    }
}
