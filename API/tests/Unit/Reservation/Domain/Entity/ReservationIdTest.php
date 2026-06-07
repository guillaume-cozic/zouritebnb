<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Domain\Entity;

use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Exception\InvalidReservationIdException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ReservationIdTest extends TestCase
{
    public function test_should_create_from_uuid(): void
    {
        $uuid = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');

        $id = new ReservationId($uuid);

        self::assertTrue($uuid->equals($id->toUuid()));
        self::assertSame('01961e2f-dead-7000-beef-000000000001', $id->toString());
    }

    public function test_should_throw_when_value_is_null(): void
    {
        $this->expectException(InvalidReservationIdException::class);
        $this->expectExceptionMessage('Reservation id is required.');

        new ReservationId(null);
    }
}
