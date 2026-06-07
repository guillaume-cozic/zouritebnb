<?php

declare(strict_types=1);

namespace App\Tests\Unit\Team\Domain\Entity;

use App\Team\Domain\Entity\Bic;
use App\Team\Domain\Exception\InvalidBankAccountException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BicTest extends TestCase
{
    public function test_should_accept_an_eight_character_bic(): void
    {
        $bic = new Bic('BNPAFRPP');

        self::assertSame('BNPAFRPP', $bic->value());
    }

    public function test_should_accept_an_eleven_character_bic(): void
    {
        $bic = new Bic('BNPAFRPPXXX');

        self::assertSame('BNPAFRPPXXX', $bic->value());
    }

    public function test_should_normalize_to_uppercase_and_strip_whitespace(): void
    {
        $bic = new Bic(' bnpa frpp xxx ');

        self::assertSame('BNPAFRPPXXX', $bic->value());
    }

    public function test_should_throw_when_value_is_null(): void
    {
        $this->expectException(InvalidBankAccountException::class);
        $this->expectExceptionMessage('BIC "" has an invalid format.');

        new Bic(null);
    }

    #[DataProvider('invalidBics')]
    public function test_should_throw_when_format_is_invalid(string $value): void
    {
        $this->expectException(InvalidBankAccountException::class);
        $this->expectExceptionMessage(\sprintf('BIC "%s" has an invalid format.', $value));

        new Bic($value);
    }

    public static function invalidBics(): \Generator
    {
        yield 'empty' => [''];
        yield 'too short' => ['BNPAFR'];
        yield 'digits in bank code' => ['BN1AFRPP'];
        yield 'wrong country length' => ['BNPAF1PP'];
        yield 'too long' => ['BNPAFRPPXXXX'];
        yield 'ten characters' => ['BNPAFRPPXX'];
    }
}
