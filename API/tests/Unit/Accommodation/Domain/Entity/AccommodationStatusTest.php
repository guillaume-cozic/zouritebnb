<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Entity\AccommodationStatus;
use PHPUnit\Framework\TestCase;

final class AccommodationStatusTest extends TestCase
{
    public function test_should_expose_string_values(): void
    {
        self::assertSame('draft', AccommodationStatus::Draft->value);
        self::assertSame('published', AccommodationStatus::Published->value);
    }

    public function test_should_build_from_string(): void
    {
        self::assertSame(AccommodationStatus::Draft, AccommodationStatus::from('draft'));
        self::assertSame(AccommodationStatus::Published, AccommodationStatus::from('published'));
    }

    public function test_should_expose_all_cases(): void
    {
        self::assertSame(
            [AccommodationStatus::Draft, AccommodationStatus::Published],
            AccommodationStatus::cases(),
        );
    }
}
