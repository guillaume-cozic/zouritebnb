<?php

declare(strict_types=1);

namespace App\User\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UploadHostAvatarCommand
{
    public function __construct(
        public Uuid $userId,
        public string $content,
        public string $mimeType,
        public int $size,
    ) {
    }
}
