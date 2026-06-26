<?php

declare(strict_types=1);

namespace App\User\Application\UseCase;

use App\Shared\Domain\Port\Clock;
use App\User\Domain\Command\VerifyEmailCommand;
use App\User\Domain\Entity\UserToken;
use App\User\Domain\Entity\UserTokenPurpose;
use App\User\Domain\Exception\InvalidEmailVerificationTokenException;
use App\User\Domain\Port\UserRepository;
use App\User\Domain\Port\UserTokenRepository;

final readonly class VerifyEmail
{
    public function __construct(
        private UserRepository $users,
        private UserTokenRepository $tokens,
        private Clock $clock,
    ) {
    }

    public function handle(VerifyEmailCommand $command): void
    {
        $now = $this->clock->now();
        $token = $this->tokens->findByHash(UserToken::hash($command->token));

        if (null === $token || !$token->isUsable(UserTokenPurpose::EmailVerification, $now)) {
            throw InvalidEmailVerificationTokenException::becauseInvalidOrExpired();
        }

        $user = $this->users->findById($token->getUserId());
        if (null === $user) {
            throw InvalidEmailVerificationTokenException::becauseInvalidOrExpired();
        }

        $user->markEmailVerified($now);
        $token->markUsed($now);

        $this->users->save($user);
        $this->tokens->save($token);
    }
}
