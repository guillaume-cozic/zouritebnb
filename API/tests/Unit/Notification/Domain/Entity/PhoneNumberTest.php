<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Domain\Entity;

use App\Notification\Domain\Entity\PhoneNumber;
use App\Notification\Domain\Exception\InvalidPhoneNumberException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    #[DataProvider('validNumbers')]
    public function test_should_accept_valid_numbers(string $value, string $expected): void
    {
        self::assertSame($expected, (new PhoneNumber($value))->toString());
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function validNumbers(): \Generator
    {
        yield 'international' => ['+230 5 123 4567', '+230 5 123 4567'];
        yield 'local with separators' => ['05-12-34-56', '05-12-34-56'];
        yield 'trimmed' => ['  +23057654321  ', '+23057654321'];
    }

    #[DataProvider('invalidNumbers')]
    public function test_should_reject_invalid_numbers(?string $value): void
    {
        $this->expectException(InvalidPhoneNumberException::class);

        new PhoneNumber($value);
    }

    /**
     * @return \Generator<string, array{?string}>
     */
    public static function invalidNumbers(): \Generator
    {
        yield 'null' => [null];
        yield 'empty' => [''];
        yield 'too short' => ['12345'];
        yield 'letters' => ['call-me'];
    }
}
