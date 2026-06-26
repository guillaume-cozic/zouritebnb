<?php

declare(strict_types=1);

namespace App\User\Application\UseCase;

use App\Shared\Domain\Port\Clock;
use App\User\Domain\Command\ResetPasswordCommand;
use App\User\Domain\Entity\UserToken;
use App\User\Domain\Entity\UserTokenPurpose;
use App\User\Domain\Exception\InvalidPasswordResetTokenException;
use App\User\Domain\Port\PasswordHasher;
use App\User\Domain\Port\UserRepository;
use App\User\Domain\Port\UserTokenRepository;

final readonly class ResetPassword
{
    public function __construct(
        private UserRepository $users,
        private UserTokenRepository $tokens,
        private PasswordHasher $hasher,
        private Clock $clock,
    ) {
    }

    public function handle(ResetPasswordCommand $command): void
    {
        $now = $this->clock->now();
        $token = $this->tokens->findByHash(UserToken::hash($command->token));

        if (null === $token || !$token->isUsable(UserTokenPurpose::PasswordReset, $now)) {
            throw InvalidPasswordResetTokenException::becauseInvalidOrExpired();
        }

        $user = $this->users->findById($token->getUserId());
        if (null === $user) {
            throw InvalidPasswordResetTokenException::becauseInvalidOrExpired();
        }

        $user->changePassword($this->hasher->hash($command->newPassword));
        $token->markUsed($now);

        $this->users->save($user);
        $this->tokens->save($token);
    }
}
