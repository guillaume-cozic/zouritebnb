<?php

declare(strict_types=1);

namespace App\User\Application\UseCase;

use App\Shared\Domain\Event\EmailVerificationRequested;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\EventBus;
use App\User\Domain\Command\RequestEmailVerificationCommand;
use App\User\Domain\Entity\UserToken;
use App\User\Domain\Entity\UserTokenPurpose;
use App\User\Domain\Port\TokenGenerator;
use App\User\Domain\Port\UserRepository;
use App\User\Domain\Port\UserTokenRepository;
use Symfony\Component\Uid\Uuid;

final readonly class RequestEmailVerification
{
    private const string TOKEN_TTL = '+24 hours';

    public function __construct(
        private UserRepository $users,
        private UserTokenRepository $tokens,
        private TokenGenerator $tokenGenerator,
        private Clock $clock,
        private EventBus $eventBus,
    ) {
    }

    public function handle(RequestEmailVerificationCommand $command): void
    {
        $user = $this->users->findById($command->userId);

        // Nothing to do if the account vanished or the email is already verified.
        if (null === $user || $user->isEmailVerified()) {
            return;
        }

        $this->tokens->deleteUsableFor($user->getId(), UserTokenPurpose::EmailVerification);

        $rawToken = $this->tokenGenerator->generate();

        $this->tokens->save(new UserToken(
            id: Uuid::v7(),
            userId: $user->getId(),
            purpose: UserTokenPurpose::EmailVerification,
            hashedToken: UserToken::hash($rawToken),
            expiresAt: $this->clock->now()->modify(self::TOKEN_TTL),
        ));

        $this->eventBus->dispatch([new EmailVerificationRequested($user->getId(), $rawToken)]);
    }
}
