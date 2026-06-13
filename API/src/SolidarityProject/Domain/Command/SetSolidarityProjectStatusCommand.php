<?php

declare(strict_types=1);

namespace App\SolidarityProject\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class SetSolidarityProjectStatusCommand
{
    public function __construct(
        public Uuid $projectId,
        public string $status,
    ) {
    }
}
