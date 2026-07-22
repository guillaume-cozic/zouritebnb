<?php

declare(strict_types=1);

namespace App\ActivityPoint\Domain\Port;

use App\ActivityPoint\Domain\Entity\ActivityPoint;
use Symfony\Component\Uid\Uuid;

interface ActivityPointRepository
{
    public function save(ActivityPoint $point): void;

    public function findById(Uuid $id): ?ActivityPoint;

    public function remove(Uuid $id): void;
}
