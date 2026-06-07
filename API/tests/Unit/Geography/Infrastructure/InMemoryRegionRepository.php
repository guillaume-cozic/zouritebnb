<?php

declare(strict_types=1);

namespace App\Tests\Unit\Geography\Infrastructure;

use App\Geography\Domain\Entity\Region;
use App\Geography\Domain\Port\RegionRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryRegionRepository implements RegionRepository
{
    /** @var Region[] */
    private array $regions = [];

    public function save(Region $region): void
    {
        $this->regions[$region->getId()->toString()] = $region;
    }

    public function findById(Uuid $id): ?Region
    {
        return $this->regions[$id->toString()] ?? null;
    }

    public function findByCode(string $code): ?Region
    {
        foreach ($this->regions as $region) {
            if ($region->getCode() === $code) {
                return $region;
            }
        }

        return null;
    }

    public function findAll(): array
    {
        return array_values($this->regions);
    }
}
