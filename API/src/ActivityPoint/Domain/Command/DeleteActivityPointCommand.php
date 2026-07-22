<?php

declare(strict_types=1);

namespace App\ActivityPoint\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class DeleteActivityPointCommand
{
    public function __construct(
        public Uuid $id,
    ) {
    }
}
