<?php

declare(strict_types=1);

namespace App\Tests\Unit\Team\Domain\Entity;

use App\Team\Domain\Entity\BankAccount;
use App\Team\Domain\Entity\Bic;
use App\Team\Domain\Entity\Iban;
use App\Team\Domain\Exception\InvalidBankAccountException;
use PHPUnit\Framework\TestCase;

final class BankAccountTest extends TestCase
{
    public function test_should_create_a_valid_bank_account(): void
    {
        $iban = new Iban('FR7630006000011234567890189');
        $bic = new Bic('BNPAFRPPXXX');

        $account = new BankAccount(iban: $iban, bic: $bic, holderName: 'John Doe');

        self::assertSame($iban, $account->iban);
        self::assertSame($bic, $account->bic);
        self::assertSame('John Doe', $account->holderName);
    }

    public function test_should_accept_null_bic(): void
    {
        $account = new BankAccount(
            iban: new Iban('FR7630006000011234567890189'),
            bic: null,
            holderName: 'John Doe',
        );

        self::assertNull($account->bic);
    }

    public function test_should_trim_holder_name(): void
    {
        $account = new BankAccount(
            iban: new Iban('FR7630006000011234567890189'),
            bic: null,
            holderName: '  John Doe  ',
        );

        self::assertSame('John Doe', $account->holderName);
    }

    public function test_should_throw_when_holder_name_is_empty(): void
    {
        $this->expectException(InvalidBankAccountException::class);
        $this->expectExceptionMessage('Account holder name must not be empty.');

        new BankAccount(
            iban: new Iban('FR7630006000011234567890189'),
            bic: null,
            holderName: '   ',
        );
    }

    public function test_should_throw_when_holder_name_is_too_long(): void
    {
        $this->expectException(InvalidBankAccountException::class);
        $this->expectExceptionMessage('Account holder name must not exceed 70 characters, got 71.');

        new BankAccount(
            iban: new Iban('FR7630006000011234567890189'),
            bic: null,
            holderName: str_repeat('a', 71),
        );
    }

    public function test_should_accept_holder_name_at_max_length(): void
    {
        $account = new BankAccount(
            iban: new Iban('FR7630006000011234567890189'),
            bic: null,
            holderName: str_repeat('a', 70),
        );

        self::assertSame(70, mb_strlen($account->holderName));
    }
}
