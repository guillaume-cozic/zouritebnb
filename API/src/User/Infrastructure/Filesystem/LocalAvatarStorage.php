<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Filesystem;

use App\User\Domain\Port\AvatarStorage;

/**
 * Stores host avatars in the same public directory as accommodation photos, so they are
 * served by the shared ServePhotoController route (/uploads/photos/{filename}).
 */
final readonly class LocalAvatarStorage implements AvatarStorage
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
