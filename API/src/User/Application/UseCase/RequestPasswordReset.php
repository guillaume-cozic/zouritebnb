<?php

declare(strict_types=1);

namespace App\User\Application\UseCase;

use App\Shared\Domain\Event\PasswordResetRequested;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\EventBus;
use App\User\Domain\Command\RequestPasswordResetCommand;
use App\User\Domain\Entity\UserToken;
use App\User\Domain\Entity\UserTokenPurpose;
use App\User\Domain\Port\TokenGenerator;
use App\User\Domain\Port\UserRepository;
use App\User\Domain\Port\UserTokenRepository;
use Symfony\Component\Uid\Uuid;

final readonly class RequestPasswordReset
{
    private const string TOKEN_TTL = '+1 hour';

    public function __construct(
        private UserRepository $users,
        private UserTokenRepository $tokens,
        private TokenGenerator $tokenGenerator,
        private Clock $clock,
        private EventBus $eventBus,
    ) {
    }

    public function handle(RequestPasswordResetCommand $command): void
    {
        $user = $this->users->findByEmail($command->email);

        // Silently no-op for unknown emails so the endpoint cannot be used to probe
        // which addresses have an account (user enumeration).
        if (null === $user) {
            return;
        }

        // A fresh link supersedes any previous one still pending for this user.
        $this->tokens->deleteUsableFor($user->getId(), UserTokenPurpose::PasswordReset);

        $rawToken = $this->tokenGenerator->generate();

        $this->tokens->save(new UserToken(
            id: Uuid::v7(),
            userId: $user->getId(),
            purpose: UserTokenPurpose::PasswordReset,
            hashedToken: UserToken::hash($rawToken),
            expiresAt: $this->clock->now()->modify(self::TOKEN_TTL),
        ));

        $this->eventBus->dispatch([new PasswordResetRequested($user->getId(), $rawToken)]);
    }
}
