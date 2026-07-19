<?php

declare(strict_types=1);

namespace App\Conversation\Infrastructure\Gd;

use App\Conversation\Domain\Exception\InvalidAttachmentException;
use App\Conversation\Domain\Port\AttachmentImageTransformer;

final readonly class GdAttachmentImageTransformer implements AttachmentImageTransformer
{
    public function toWebp(string $content): string
    {
        $image = @imagecreatefromstring($content);

        if (false === $image) {
            throw InvalidAttachmentException::becauseNotAnImage();
        }

        ob_start();
        imagewebp($image, null, 80);

        return ob_get_clean();
    }
}
