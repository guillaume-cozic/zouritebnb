<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Domain\Entity;

use App\Reservation\Domain\Entity\ReservationStatus;
use PHPUnit\Framework\TestCase;

final class ReservationStatusTest extends TestCase
{
    public function test_should_expose_all_cases_with_their_string_values(): void
    {
        self::assertSame('pending', ReservationStatus::Pending->value);
        self::assertSame('confirmed', ReservationStatus::Confirmed->value);
        self::assertSame('cancelled', ReservationStatus::Cancelled->value);
        self::assertSame('refused', ReservationStatus::Refused->value);
    }

    public function test_should_build_from_string_value(): void
    {
        self::assertSame(ReservationStatus::Pending, ReservationStatus::from('pending'));
        self::assertSame(ReservationStatus::Confirmed, ReservationStatus::from('confirmed'));
        self::assertSame(ReservationStatus::Cancelled, ReservationStatus::from('cancelled'));
        self::assertSame(ReservationStatus::Refused, ReservationStatus::from('refused'));
    }

    public function test_should_expose_exactly_four_cases(): void
    {
        self::assertCount(4, ReservationStatus::cases());
    }
}
