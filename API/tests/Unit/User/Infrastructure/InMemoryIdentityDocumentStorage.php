<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure;

use App\User\Domain\Port\IdentityDocumentStorage;

final class InMemoryIdentityDocumentStorage implements IdentityDocumentStorage
{
    /** @var array<string, string> */
    public array $stored = [];

    public function store(string $filename, string $content): void
    {
        $this->stored[$filename] = $content;
    }

    public function delete(string $filename): void
    {
        unset($this->stored[$filename]);
    }
}
