<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Entity;

use App\Conversation\Domain\Exception\InvalidAttachmentException;

final readonly class MessageAttachment
{
    public function __construct(private ?string $filename)
    {
        $this->validate();
    }

    private function validate(): void
    {
        if (null === $this->filename || '' === trim($this->filename)) {
            throw InvalidAttachmentException::becauseFilenameEmpty();
        }
    }

    public function filename(): string
    {
        return $this->filename;
    }
}
