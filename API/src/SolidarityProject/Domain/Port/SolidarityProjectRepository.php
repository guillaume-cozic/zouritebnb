<?php

declare(strict_types=1);

namespace App\SolidarityProject\Domain\Port;

use App\SolidarityProject\Domain\Entity\SolidarityProject;
use Symfony\Component\Uid\Uuid;

interface SolidarityProjectRepository
{
    public function save(SolidarityProject $project): void;

    public function findById(Uuid $id): ?SolidarityProject;

    /**
     * @return SolidarityProject[]
     */
    public function findAllActive(): array;

    public function markAsDefault(Uuid $id): void;
}
