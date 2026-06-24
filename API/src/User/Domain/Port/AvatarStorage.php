<?php

declare(strict_types=1);

namespace App\User\Domain\Port;

interface AvatarStorage
{
    public function store(string $filename, string $content): void;

    public function delete(string $filename): void;
}
