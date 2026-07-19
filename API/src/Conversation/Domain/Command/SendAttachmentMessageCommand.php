<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Command;

final readonly class SendAttachmentMessageCommand
{
    public function __construct(
        public string $conversationId,
        public string $authorUserId,
        public ?string $body,
        public string $content,
        public string $mimeType,
        public int $size,
    ) {
    }
}
