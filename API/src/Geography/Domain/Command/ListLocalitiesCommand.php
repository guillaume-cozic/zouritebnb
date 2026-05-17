<?php

declare(strict_types=1);

namespace App\Geography\Domain\Command;

final readonly class ListLocalitiesCommand
{
    public function __construct(
        public ?string $regionCode = null,
    ) {
    }
}
