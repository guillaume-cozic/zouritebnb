<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Domain\Entity;

use App\Reservation\Domain\Entity\GuestCount;
use App\Reservation\Domain\Exception\InvalidGuestCountException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GuestCountTest extends TestCase
{
    public function test_should_expose_the_number_of_guests(): void
    {
        $count = new GuestCount(3);

        self::assertSame(3, $count->value());
    }

    public function test_should_accept_a_single_guest(): void
    {
        self::assertSame(1, new GuestCount(1)->value());
    }

    public function test_should_reject_a_null_value(): void
    {
        $this->expectException(InvalidGuestCountException::class);
        $this->expectExceptionMessage('Guest count is required.');

        new GuestCount(null);
    }

    #[DataProvider('nonPositiveValues')]
    public function test_should_reject_a_non_positive_value(int $value): void
    {
        $this->expectException(InvalidGuestCountException::class);
        $this->expectExceptionMessage('Guest count must be at least 1');

        new GuestCount($value);
    }

    public static function nonPositiveValues(): \Generator
    {
        yield 'zero' => [0];
        yield 'negative' => [-2];
    }

    public function test_should_reject_a_value_above_the_maximum(): void
    {
        $this->expectException(InvalidGuestCountException::class);
        $this->expectExceptionMessage('Guest count must not exceed 100');

        new GuestCount(101);
    }
}
