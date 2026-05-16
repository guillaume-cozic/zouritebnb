<?php

declare(strict_types=1);

namespace App\Team\Domain\Entity;

use App\Team\Domain\Exception\InvalidBankAccountException;

final readonly class BankAccount
{
    private const int HOLDER_NAME_MAX_LENGTH = 70;

    public Iban $iban;
    public ?Bic $bic;
    public string $holderName;

    public function __construct(Iban $iban, ?Bic $bic, string $holderName)
    {
        $trimmed = trim($holderName);
        if ('' === $trimmed) {
            throw InvalidBankAccountException::becauseHolderNameEmpty();
        }
        if (mb_strlen($trimmed) > self::HOLDER_NAME_MAX_LENGTH) {
            throw InvalidBankAccountException::becauseHolderNameTooLong(mb_strlen($trimmed));
        }

        $this->iban = $iban;
        $this->bic = $bic;
        $this->holderName = $trimmed;
    }
}
