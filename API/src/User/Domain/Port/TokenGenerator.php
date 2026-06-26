<?php

declare(strict_types=1);

namespace App\User\Domain\Port;

interface TokenGenerator
{
    /**
     * Generates a cryptographically secure, URL-safe random token (the raw secret
     * embedded in email links). Only its hash is persisted.
     */
    public function generate(): string;
}
