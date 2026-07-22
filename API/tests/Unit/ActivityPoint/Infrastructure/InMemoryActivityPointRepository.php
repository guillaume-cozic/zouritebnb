<?php

declare(strict_types=1);

namespace App\Tests\Unit\ActivityPoint\Infrastructure;

use App\ActivityPoint\Domain\Entity\ActivityPoint;
use App\ActivityPoint\Domain\Port\ActivityPointRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryActivityPointRepository implements ActivityPointRepository
{
    /** @var array<string, ActivityPoint> */
    private array $points = [];

    public function save(ActivityPoint $point): void
    {
        $this->points[$point->getId()->toRfc4122()] = $point;
    }

    public function findById(Uuid $id): ?ActivityPoint
    {
        return $this->points[$id->toRfc4122()] ?? null;
    }

    public function remove(Uuid $id): void
    {
        unset($this->points[$id->toRfc4122()]);
    }
}
