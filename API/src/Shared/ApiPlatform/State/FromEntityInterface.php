<?php

declare(strict_types=1);

namespace App\Shared\ApiPlatform\State;

interface FromEntityInterface
{
    public static function fromEntity(object $entity): static;
}
