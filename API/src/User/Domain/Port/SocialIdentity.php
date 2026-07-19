<?php

declare(strict_types=1);

namespace App\User\Domain\Port;

use App\User\Domain\Entity\SocialProvider;

/**
 * Identity attested by a social provider after its token has been verified.
 */
final readonly class SocialIdentity
{
    public function __construct(
        public SocialProvider $provider,
        public string $email,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public bool $emailVerified = false,
    ) {
    }
}
