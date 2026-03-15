<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Port;

use App\Accommodation\Domain\Entity\Accommodation;
use Symfony\Component\Uid\Uuid;

interface AccommodationRepository
{
    public function findById(Uuid $id): ?Accommodation;

    public function save(Accommodation $accommodation): void;
}
