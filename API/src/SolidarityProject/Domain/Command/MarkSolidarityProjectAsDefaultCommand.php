<?php

declare(strict_types=1);

namespace App\SolidarityProject\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class MarkSolidarityProjectAsDefaultCommand
{
    public function __construct(public Uuid $projectId)
    {
    }
}
