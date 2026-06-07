<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Domain\Entity;

use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Exception\InvalidDateRangeException;
use PHPUnit\Framework\TestCase;

final class DateRangeTest extends TestCase
{
    public function test_should_create_a_valid_date_range(): void
    {
        $checkIn = new \DateTimeImmutable('2026-04-13T15:00:00+00:00');
        $checkOut = new \DateTimeImmutable('2026-04-15T11:00:00+00:00');

        $range = new DateRange($checkIn, $checkOut);

        self::assertSame($checkIn, $range->checkIn());
        self::assertSame($checkOut, $range->checkOut());
    }

    public function test_should_throw_when_check_out_equals_check_in(): void
    {
        $date = new \DateTimeImmutable('2026-04-13T15:00:00+00:00');

        $this->expectException(InvalidDateRangeException::class);
        $this->expectExceptionMessage('Check-out date must be strictly after check-in date.');

        new DateRange($date, $date);
    }

    public function test_should_throw_when_check_out_before_check_in(): void
    {
        $checkIn = new \DateTimeImmutable('2026-04-15T15:00:00+00:00');
        $checkOut = new \DateTimeImmutable('2026-04-13T11:00:00+00:00');

        $this->expectException(InvalidDateRangeException::class);
        $this->expectExceptionMessage('Check-out date must be strictly after check-in date.');

        new DateRange($checkIn, $checkOut);
    }
}
