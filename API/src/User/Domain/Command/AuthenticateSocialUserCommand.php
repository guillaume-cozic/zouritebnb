<?php

declare(strict_types=1);

namespace App\User\Domain\Command;

use App\User\Domain\Entity\SocialProvider;
use Symfony\Component\Uid\Uuid;

final readonly class AuthenticateSocialUserCommand
{
    public function __construct(
        public SocialProvider $provider,
        public string $token,
        /** Team to attach the user to if this social identity registers a new account. */
        public Uuid $teamId,
    ) {
    }
}
