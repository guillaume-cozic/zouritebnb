<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Infrastructure;

use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Port\AccommodationRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryAccommodationRepository implements AccommodationRepository
{
    /** @var Accommodation[] */
    private array $accommodations = [];

    public function findById(Uuid $id): ?Accommodation
    {
        return $this->accommodations[$id->toRfc4122()] ?? null;
    }

    public function save(Accommodation $accommodation): void
    {
        $this->accommodations[$accommodation->getId()->toRfc4122()] = $accommodation;
    }
}
