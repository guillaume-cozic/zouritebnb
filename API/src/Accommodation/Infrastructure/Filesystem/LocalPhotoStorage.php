<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\Filesystem;

use App\Accommodation\Domain\Port\PhotoStorage;

final readonly class LocalPhotoStorage implements PhotoStorage
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
