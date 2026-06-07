<?php

declare(strict_types=1);

namespace App\Tests\Unit\Geography\Infrastructure;

use App\Geography\Domain\Entity\Locality;
use App\Geography\Domain\Port\LocalityRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryLocalityRepository implements LocalityRepository
{
    /** @var Locality[] */
    private array $localities = [];

    /** @var array<string, string> regionId => regionCode */
    private array $regionCodes = [];

    public function save(Locality $locality): void
    {
        $this->localities[$locality->getId()->toString()] = $locality;
    }

    public function registerRegionCode(Uuid $regionId, string $regionCode): void
    {
        $this->regionCodes[$regionId->toString()] = $regionCode;
    }

    public function findById(Uuid $id): ?Locality
    {
        return $this->localities[$id->toString()] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->localities);
    }

    public function findByRegionId(Uuid $regionId): array
    {
        return array_values(array_filter(
            $this->localities,
            static fn (Locality $locality): bool => $locality->getRegionId()->equals($regionId),
        ));
    }

    public function findByRegionCode(string $regionCode): array
    {
        return array_values(array_filter(
            $this->localities,
            fn (Locality $locality): bool => ($this->regionCodes[$locality->getRegionId()->toString()] ?? null) === $regionCode,
        ));
    }
}
