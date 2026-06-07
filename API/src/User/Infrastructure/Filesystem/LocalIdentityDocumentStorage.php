<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Filesystem;

use App\User\Domain\Port\IdentityDocumentStorage;

final readonly class LocalIdentityDocumentStorage implements IdentityDocumentStorage
{
    public function __construct(
        private string $uploadDir,
    ) {
    }

    public function store(string $filename, string $content): void
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0o755, true);
        }

        file_put_contents($this->uploadDir.'/'.$filename, $content);
    }

    public function delete(string $filename): void
    {
        $path = $this->uploadDir.'/'.$filename;

        if (file_exists($path)) {
            unlink($path);
        }
    }
}
