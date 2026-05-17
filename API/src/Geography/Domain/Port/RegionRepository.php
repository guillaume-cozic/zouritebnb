<?php

declare(strict_types=1);

namespace App\Geography\Domain\Port;

use App\Geography\Domain\Entity\Region;
use Symfony\Component\Uid\Uuid;

interface RegionRepository
{
    public function save(Region $region): void;

    public function findById(Uuid $id): ?Region;

    public function findByCode(string $code): ?Region;

    /**
     * @return Region[]
     */
    public function findAll(): array;
}
