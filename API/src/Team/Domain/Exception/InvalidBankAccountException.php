<?php

declare(strict_types=1);

namespace App\Team\Domain\Exception;

final class InvalidBankAccountException extends \DomainException
{
    public static function becauseIbanEmpty(): self
    {
        return new self('IBAN must not be empty.');
    }

    public static function becauseIbanFormatInvalid(string $iban): self
    {
        return new self(\sprintf('IBAN "%s" has an invalid format.', $iban));
    }

    public static function becauseIbanChecksumInvalid(string $iban): self
    {
        return new self(\sprintf('IBAN "%s" failed the mod-97 checksum verification.', $iban));
    }

    public static function becauseBicFormatInvalid(string $bic): self
    {
        return new self(\sprintf('BIC "%s" has an invalid format.', $bic));
    }

    public static function becauseHolderNameEmpty(): self
    {
        return new self('Account holder name must not be empty.');
    }

    public static function becauseHolderNameTooLong(int $length): self
    {
        return new self(\sprintf('Account holder name must not exceed 70 characters, got %d.', $length));
    }
}
