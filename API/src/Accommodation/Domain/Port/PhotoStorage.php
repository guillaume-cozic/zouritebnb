<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Port;

interface PhotoStorage
{
    public function store(string $filename, string $content): void;

    public function delete(string $filename): void;
}
