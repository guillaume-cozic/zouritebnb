<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UnpublishAccommodationCommand
{
    public function __construct(
        public Uuid $id,
    ) {
    }
}
