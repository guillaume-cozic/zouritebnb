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

    public function handle(AuthenticateUserCommand $command): User
    {
        $user = $this->repository->findByEmail($command->email);

        if (null === $user || !$this->hasher->verify($command->password, $user->getHashedPassword())) {
            throw InvalidCredentialsException::create();
        }

        return $user;
    }
}
