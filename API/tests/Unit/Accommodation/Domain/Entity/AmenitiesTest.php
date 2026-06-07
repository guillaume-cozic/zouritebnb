<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Entity\Amenities;
use App\Accommodation\Domain\Exception\InvalidAmenitiesException;
use PHPUnit\Framework\TestCase;

final class AmenitiesTest extends TestCase
{
    public function test_should_create_valid_amenities(): void
    {
        $amenities = new Amenities(['wifi', 'pool', 'parking']);

        self::assertSame(['wifi', 'pool', 'parking'], $amenities->codes());
    }

    public function test_should_accept_empty_list(): void
    {
        $amenities = new Amenities([]);

        self::assertSame([], $amenities->codes());
    }

    public function test_should_throw_when_code_is_empty(): void
    {
        $this->expectException(InvalidAmenitiesException::class);
        $this->expectExceptionMessage('Amenity code must not be empty.');

        new Amenities(['wifi', '   ']);
    }

    public function test_should_throw_when_code_is_not_a_string(): void
    {
        $this->expectException(InvalidAmenitiesException::class);
        $this->expectExceptionMessage('Each amenity code must be a non-empty string, got "int".');

        /* @phpstan-ignore-next-line intentionally invalid type to test the guard */
        new Amenities(['wifi', 42]);
    }
}
