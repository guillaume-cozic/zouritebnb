<?php

declare(strict_types=1);

namespace App\Conversation\Infrastructure\Filesystem;

use App\Conversation\Domain\Port\AttachmentStorage;

final readonly class LocalAttachmentStorage implements AttachmentStorage
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
}
