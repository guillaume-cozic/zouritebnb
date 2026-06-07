<?php

declare(strict_types=1);

namespace App\Tests\Unit\Review\Domain\Entity;

use App\Review\Domain\Entity\Rating;
use App\Review\Domain\Exception\InvalidRatingException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RatingTest extends TestCase
{
    #[DataProvider('validRatings')]
    public function test_should_accept_a_rating_between_1_and_5(int $value): void
    {
        $rating = new Rating($value);

        self::assertSame($value, $rating->toInt());
    }

    public static function validRatings(): \Generator
    {
        yield 'minimum' => [1];
        yield 'middle' => [3];
        yield 'maximum' => [5];
    }

    public function test_should_throw_when_rating_is_null(): void
    {
        $this->expectException(InvalidRatingException::class);
        $this->expectExceptionMessage('Rating is required.');

        new Rating(null);
    }

    #[DataProvider('outOfBoundsRatings')]
    public function test_should_throw_when_rating_is_out_of_bounds(int $value): void
    {
        $this->expectException(InvalidRatingException::class);
        $this->expectExceptionMessage(\sprintf('Rating must be an integer between 1 and 5, got %d.', $value));

        new Rating($value);
    }

    public static function outOfBoundsRatings(): \Generator
    {
        yield 'zero' => [0];
        yield 'negative' => [-3];
        yield 'too high' => [6];
    }
}
