<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Infrastructure;

use App\Conversation\Domain\Port\AttachmentImageTransformer;

final class FakeAttachmentImageTransformer implements AttachmentImageTransformer
{
    public function toWebp(string $content): string
    {
        return 'webp:'.$content;
    }
}
