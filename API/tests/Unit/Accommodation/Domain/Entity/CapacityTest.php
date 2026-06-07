<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Entity\Capacity;
use App\Accommodation\Domain\Exception\InvalidCapacityException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CapacityTest extends TestCase
{
    public function test_should_create_valid_capacity(): void
    {
        $capacity = new Capacity(
            bedrooms: 3,
            bathrooms: 2,
            maxGuests: 6,
            singleBeds: 2,
            doubleBeds: 2,
        );

        self::assertSame(3, $capacity->bedrooms());
        self::assertSame(2, $capacity->bathrooms());
        self::assertSame(6, $capacity->maxGuests());
        self::assertSame(2, $capacity->singleBeds());
        self::assertSame(2, $capacity->doubleBeds());
    }

    public function test_should_accept_zero_values(): void
    {
        $capacity = new Capacity(
            bedrooms: 0,
            bathrooms: 0,
            maxGuests: 0,
            singleBeds: 0,
            doubleBeds: 0,
        );

        self::assertSame(0, $capacity->bedrooms());
        self::assertSame(0, $capacity->maxGuests());
    }

    #[DataProvider('negativeFieldProvider')]
    public function test_should_throw_when_a_field_is_negative(array $args, string $field): void
    {
        $this->expectException(InvalidCapacityException::class);
        $this->expectExceptionMessage(\sprintf('The field "%s" must be >= 0, got -1.', $field));

        new Capacity(...$args);
    }

    public static function negativeFieldProvider(): \Generator
    {
        $base = [
            'bedrooms' => 1,
            'bathrooms' => 1,
            'maxGuests' => 1,
            'singleBeds' => 1,
            'doubleBeds' => 1,
        ];

        foreach (array_keys($base) as $field) {
            $args = $base;
            $args[$field] = -1;

            yield $field => [$args, $field];
        }
    }
}
