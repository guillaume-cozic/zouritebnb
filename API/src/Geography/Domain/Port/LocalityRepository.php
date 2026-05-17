<?php

declare(strict_types=1);

namespace App\Geography\Domain\Port;

use App\Geography\Domain\Entity\Locality;
use Symfony\Component\Uid\Uuid;

interface LocalityRepository
{
    public function save(Locality $locality): void;

    public function findById(Uuid $id): ?Locality;

    /**
     * @return Locality[]
     */
    public function findAll(): array;

    /**
     * @return Locality[]
     */
    public function findByRegionId(Uuid $regionId): array;

    /**
     * @return Locality[]
     */
    public function findByRegionCode(string $regionCode): array;
}
