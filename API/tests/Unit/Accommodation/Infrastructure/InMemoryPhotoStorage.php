<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Infrastructure;

use App\Accommodation\Domain\Port\PhotoStorage;

final class InMemoryPhotoStorage implements PhotoStorage
{
    /** @var array<string, string> */
    private array $files = [];

    public function store(string $filename, string $content): void
    {
        $this->files[$filename] = $content;
    }

    public function delete(string $filename): void
    {
        unset($this->files[$filename]);
    }

    public function has(string $filename): bool
    {
        return isset($this->files[$filename]);
    }

    public function get(string $filename): ?string
    {
        return $this->files[$filename] ?? null;
    }
}
