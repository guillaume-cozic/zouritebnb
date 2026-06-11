<?php

declare(strict_types=1);

namespace App\User\Application\UseCase;

use App\User\Domain\Command\AuthenticateUserCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\InvalidCredentialsException;
use App\User\Domain\Port\PasswordHasher;
use App\User\Domain\Port\UserRepository;

final readonly class AuthenticateUser
{
    public function __construct(
        private UserRepository $repository,
        private PasswordHasher $hasher,
    ) {
    }

    /**
     * Real bcrypt hash of a random value, verified against when the email is
     * unknown so an absent account costs the same as a wrong password — defeats
     * timing-based user enumeration. Must stay a valid bcrypt hash, otherwise
     * password_verify() returns early and the timing equalization is lost.
     */
    private const string DUMMY_HASH = '$2y$12$R8ETdbnIj386dFVC2Yfm0uVpiN9BzGbOc.8kuGOiq5FuhoQNpkWRS';

    public function handle(AuthenticateUserCommand $command): User
    {
        $user = $this->repository->findByEmail($command->email);

        // Always run a verification (even for unknown emails) so the response
        // time does not reveal whether the account exists, then fail with a
        // single generic error covering both cases.
        $passwordValid = $this->hasher->verify(
            $command->password,
            $user?->getHashedPassword() ?? self::DUMMY_HASH,
        );

        if (null === $user || !$passwordValid) {
            throw InvalidCredentialsException::create();
        }

        return $user;
    }
}
