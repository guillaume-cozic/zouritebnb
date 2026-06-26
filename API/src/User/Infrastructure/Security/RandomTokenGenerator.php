<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Domain\Port\TokenGenerator;

final readonly class RandomTokenGenerator implements TokenGenerator
{
    public function generate(): string
    {
        // 32 bytes of entropy, hex-encoded: URL-safe and collision-resistant.
        return bin2hex(random_bytes(32));
    }
}
