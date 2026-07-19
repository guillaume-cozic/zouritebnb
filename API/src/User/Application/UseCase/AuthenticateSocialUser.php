<?php

declare(strict_types=1);

namespace App\User\Application\UseCase;

use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\EventBus;
use App\User\Domain\Command\AuthenticateSocialUserCommand;
use App\User\Domain\Entity\SocialAuthenticationResult;
use App\User\Domain\Entity\User;
use App\User\Domain\Port\PasswordHasher;
use App\User\Domain\Port\SocialIdentityVerifier;
use App\User\Domain\Port\UserRepository;
use Symfony\Component\Uid\Uuid;

final readonly class AuthenticateSocialUser
{
    public function __construct(
        private SocialIdentityVerifier $verifier,
        private UserRepository $repository,
        private PasswordHasher $hasher,
        private EventBus $eventBus,
        private Clock $clock,
    ) {
    }

    public function handle(AuthenticateSocialUserCommand $command): SocialAuthenticationResult
    {
        $identity = $this->verifier->verify($command->provider, $command->token);

        $existing = $this->repository->findByEmail($identity->email);
        if (null !== $existing) {
            return new SocialAuthenticationResult(user: $existing, registered: false);
        }

        // The account has no usable password: authentication always goes through the
        // provider. A random secret keeps the non-nullable hash column satisfied
        // without opening a guessable credential.
        $user = User::register(
            id: Uuid::v7(),
            email: $identity->email,
            hashedPassword: $this->hasher->hash(bin2hex(random_bytes(32))),
            teamId: $command->teamId,
        );
        if (null !== $identity->firstName || null !== $identity->lastName) {
            $user->updateProfile($identity->firstName, $identity->lastName, $identity->email);
        }
        if ($identity->emailVerified) {
            $user->markEmailVerified($this->clock->now());
        }

        $this->repository->save($user);
        $this->eventBus->dispatch($user->releaseEvents());

        return new SocialAuthenticationResult(user: $user, registered: true);
    }
}
