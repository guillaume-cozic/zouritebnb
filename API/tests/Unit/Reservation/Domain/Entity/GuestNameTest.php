<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Domain\Entity;

use App\Reservation\Domain\Entity\GuestName;
use App\Reservation\Domain\Exception\InvalidGuestNameException;
use PHPUnit\Framework\TestCase;

final class GuestNameTest extends TestCase
{
    public function test_should_create_a_valid_guest_name(): void
    {
        $name = new GuestName('Jane Doe');

        self::assertSame('Jane Doe', $name->toString());
    }

    public function test_should_trim_surrounding_whitespace(): void
    {
        $name = new GuestName('  Jane Doe  ');

        self::assertSame('Jane Doe', $name->toString());
    }

    public function test_should_accept_name_at_max_length(): void
    {
        $value = str_repeat('a', 255);

        $name = new GuestName($value);

        self::assertSame($value, $name->toString());
    }

    public function test_should_throw_when_value_is_null(): void
    {
        $this->expectException(InvalidGuestNameException::class);
        $this->expectExceptionMessage('Guest name must not be empty.');

        new GuestName(null);
    }

    public function test_should_throw_when_value_is_empty(): void
    {
        $this->expectException(InvalidGuestNameException::class);
        $this->expectExceptionMessage('Guest name must not be empty.');

        new GuestName('');
    }

    public function test_should_throw_when_value_is_only_whitespace(): void
    {
        $this->expectException(InvalidGuestNameException::class);
        $this->expectExceptionMessage('Guest name must not be empty.');

        new GuestName('   ');
    }

    public function test_should_throw_when_value_is_too_long(): void
    {
        $this->expectException(InvalidGuestNameException::class);
        $this->expectExceptionMessage('Guest name must not exceed 255 characters, got 256.');

        new GuestName(str_repeat('a', 256));
    }
}
