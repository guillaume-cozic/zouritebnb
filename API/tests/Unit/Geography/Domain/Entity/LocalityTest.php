<?php

declare(strict_types=1);

namespace App\Tests\Unit\Geography\Domain\Entity;

use App\Geography\Domain\Entity\Locality;
use App\Geography\Domain\Exception\InvalidLocalityException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class LocalityTest extends TestCase
{
    public function test_should_create_a_valid_locality(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $regionId = Uuid::fromString('01961e2f-dead-7000-beef-000000000002');

        $locality = new Locality(id: $id, name: 'Paris', regionId: $regionId);

        self::assertSame($id, $locality->getId());
        self::assertSame('Paris', $locality->getName());
        self::assertSame($regionId, $locality->getRegionId());
    }

    public function test_should_trim_name(): void
    {
        $locality = new Locality(
            id: Uuid::v7(),
            name: '  Lyon  ',
            regionId: Uuid::v7(),
        );

        self::assertSame('Lyon', $locality->getName());
    }

    public function test_should_throw_when_name_is_blank(): void
    {
        $this->expectException(InvalidLocalityException::class);
        $this->expectExceptionMessage('Locality name must not be blank.');

        new Locality(id: Uuid::v7(), name: '   ', regionId: Uuid::v7());
    }

    public function test_should_throw_when_name_is_empty(): void
    {
        $this->expectException(InvalidLocalityException::class);
        $this->expectExceptionMessage('Locality name must not be blank.');

        new Locality(id: Uuid::v7(), name: '', regionId: Uuid::v7());
    }
}
