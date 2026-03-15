<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Port;

use App\Accommodation\Domain\Entity\Photo;
use Symfony\Component\Uid\Uuid;

interface PhotoRepository
{
    public function save(Photo $photo): void;

    public function findById(Uuid $id): ?Photo;

    public function delete(Photo $photo): void;
}
