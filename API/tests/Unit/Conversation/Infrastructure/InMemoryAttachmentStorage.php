<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Infrastructure;

use App\Conversation\Domain\Port\AttachmentStorage;

final class InMemoryAttachmentStorage implements AttachmentStorage
{
    /** @var array<string, string> mapping filename → content */
    private array $files = [];

    public function store(string $filename, string $content): void
    {
        $this->files[$filename] = $content;
    }

    public function get(string $filename): ?string
    {
        return $this->files[$filename] ?? null;
    }

    public function count(): int
    {
        return \count($this->files);
    }
}
