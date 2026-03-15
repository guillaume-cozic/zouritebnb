<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Entity;

final readonly class TransformedImage
{
    public function __construct(
        private string $content,
        private string $mimeType,
        private int $size,
    ) {
    }

    public function content(): string
    {
        return $this->content;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function size(): int
    {
        return $this->size;
    }
}
