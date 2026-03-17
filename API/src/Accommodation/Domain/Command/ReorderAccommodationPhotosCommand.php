<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class ReorderAccommodationPhotosCommand
{
    /**
     * @param Uuid[] $photoIds
     */
    public function __construct(
        public Uuid $accommodationId,
        public array $photoIds,
    ) {
    }
}
