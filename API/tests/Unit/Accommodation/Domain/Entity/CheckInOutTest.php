<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Entity\CheckInOut;
use App\Accommodation\Domain\Exception\InvalidCheckInOutException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CheckInOutTest extends TestCase
{
    public function test_should_create_valid_check_in_out(): void
    {
        $checkInOut = new CheckInOut(checkIn: '15:00', checkOut: '11:00');

        self::assertSame('15:00', $checkInOut->checkIn());
        self::assertSame('11:00', $checkInOut->checkOut());
    }

    public function test_should_throw_when_check_in_format_is_invalid(): void
    {
        $this->expectException(InvalidCheckInOutException::class);
        $this->expectExceptionMessage('Le format d\'heure "3pm" est invalide. Utilisez le format HH:MM.');

        new CheckInOut(checkIn: '3pm', checkOut: '11:00');
    }

    public function test_should_throw_when_check_out_format_is_invalid(): void
    {
        $this->expectException(InvalidCheckInOutException::class);
        $this->expectExceptionMessage('Le format d\'heure "11h" est invalide. Utilisez le format HH:MM.');

        new CheckInOut(checkIn: '15:00', checkOut: '11h');
    }

    #[DataProvider('invalidFormatProvider')]
    public function test_should_reject_invalid_formats(string $value): void
    {
        $this->expectException(InvalidCheckInOutException::class);

        new CheckInOut(checkIn: $value, checkOut: '11:00');
    }

    public static function invalidFormatProvider(): \Generator
    {
        yield 'empty' => [''];
        yield 'single digit hour' => ['9:00'];
        yield 'no colon' => ['1500'];
        yield 'too long' => ['15:000'];
        yield 'letters' => ['ab:cd'];
    }
}
