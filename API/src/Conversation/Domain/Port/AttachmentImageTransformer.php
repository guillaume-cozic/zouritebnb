<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Port;

interface AttachmentImageTransformer
{
    /**
     * Re-encodes the uploaded image to WebP and returns the resulting bytes.
     */
    public function toWebp(string $content): string;
}
