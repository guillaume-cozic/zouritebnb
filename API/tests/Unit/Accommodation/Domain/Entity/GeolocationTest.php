<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Entity\Geolocation;
use PHPUnit\Framework\TestCase;

final class GeolocationTest extends TestCase
{
    public function test_should_expose_latitude_and_longitude(): void
    {
        $geolocation = new Geolocation(latitude: 48.8566, longitude: 2.3522);

        self::assertSame(48.8566, $geolocation->latitude());
        self::assertSame(2.3522, $geolocation->longitude());
    }

    public function test_should_accept_negative_coordinates(): void
    {
        $geolocation = new Geolocation(latitude: -33.8688, longitude: -70.6693);

        self::assertSame(-33.8688, $geolocation->latitude());
        self::assertSame(-70.6693, $geolocation->longitude());
    }
}
