<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Port;

interface AttachmentStorage
{
    public function store(string $filename, string $content): void;
}
