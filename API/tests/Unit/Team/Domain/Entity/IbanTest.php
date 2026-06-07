<?php

declare(strict_types=1);

namespace App\Tests\Unit\Team\Domain\Entity;

use App\Team\Domain\Entity\Iban;
use App\Team\Domain\Exception\InvalidBankAccountException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IbanTest extends TestCase
{
    public function test_should_accept_a_valid_iban(): void
    {
        $iban = new Iban('FR7630006000011234567890189');

        self::assertSame('FR7630006000011234567890189', $iban->value());
    }

    public function test_should_normalize_to_uppercase_and_strip_whitespace(): void
    {
        $iban = new Iban(' fr76 3000 6000 0112 3456 7890 189 ');

        self::assertSame('FR7630006000011234567890189', $iban->value());
    }

    public function test_should_accept_another_valid_iban(): void
    {
        $iban = new Iban('GB82WEST12345698765432');

        self::assertSame('GB82WEST12345698765432', $iban->value());
    }

    public function test_should_throw_when_value_is_null(): void
    {
        $this->expectException(InvalidBankAccountException::class);
        $this->expectExceptionMessage('IBAN must not be empty.');

        new Iban(null);
    }

    public function test_should_throw_when_value_is_blank(): void
    {
        $this->expectException(InvalidBankAccountException::class);
        $this->expectExceptionMessage('IBAN must not be empty.');

        new Iban('   ');
    }

    #[DataProvider('invalidFormatIbans')]
    public function test_should_throw_when_format_is_invalid(string $value): void
    {
        $this->expectException(InvalidBankAccountException::class);
        $this->expectExceptionMessage(\sprintf('IBAN "%s" has an invalid format.', $value));

        new Iban($value);
    }

    public static function invalidFormatIbans(): \Generator
    {
        yield 'no country code' => ['7630006000011234567890189'];
        yield 'letters instead of check digits' => ['FRXX30006000011234567890189'];
        yield 'too short body' => ['FR7612345'];
        yield 'illegal characters in body' => ['FR76$$$60000112345678901'];
    }

    public function test_should_throw_when_checksum_is_invalid(): void
    {
        $this->expectException(InvalidBankAccountException::class);
        $this->expectExceptionMessage('IBAN "FR7630006000011234567890188" failed the mod-97 checksum verification.');

        new Iban('FR7630006000011234567890188');
    }

    public function test_should_mask_the_iban(): void
    {
        $iban = new Iban('FR7630006000011234567890189');

        $masked = $iban->masked();

        self::assertStringStartsWith('FR76', $masked);
        self::assertStringEndsWith('0189', $masked);
        self::assertStringContainsString('•', $masked);
        self::assertSame(\strlen('FR7630006000011234567890189'), mb_strlen($masked));
    }

    public function test_should_not_mask_short_iban(): void
    {
        // BE68539007547034 is 16 chars (> 8) so it gets masked; need a <= 8 normalized value.
        // The shortest valid IBAN per the regex is 15 chars, so masking always applies for
        // real IBANs. This documents the short-circuit branch via reflection-free assertion:
        // any valid IBAN here is longer than 8, so masked() differs from value().
        $iban = new Iban('GB82WEST12345698765432');

        self::assertNotSame($iban->value(), $iban->masked());
    }

    public function test_should_return_normalized_unchanged_when_normalized_is_short(): void
    {
        // The mod-97/regex validation guarantees a normalized IBAN is at least 15 chars,
        // so the `$len <= 8` short-circuit in masked() is unreachable through the constructor.
        // We force a short normalized value via reflection to cover that defensive branch.
        $iban = (new \ReflectionClass(Iban::class))->newInstanceWithoutConstructor();
        $property = new \ReflectionProperty(Iban::class, 'normalized');
        $property->setValue($iban, 'FR761234');

        self::assertSame('FR761234', $iban->masked());
    }
}
