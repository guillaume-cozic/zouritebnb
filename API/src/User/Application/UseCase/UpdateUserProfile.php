<?php

declare(strict_types=1);

namespace App\User\Application\UseCase;

use App\User\Domain\Command\UpdateUserProfileCommand;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Port\UserRepository;

final readonly class UpdateUserProfile
{
    public function __construct(
        private UserRepository $repository,
    ) {
    }

    public function handle(UpdateUserProfileCommand $command): void
    {
        $user = $this->repository->findById($command->id);

        if (null === $user) {
            throw UserNotFoundException::becauseNotFound($command->id->toRfc4122());
        }

        if ($command->email !== $user->getEmail()) {
            $existing = $this->repository->findByEmail($command->email);
            if (null !== $existing && !$existing->getId()->equals($user->getId())) {
                throw UserAlreadyExistsException::becauseEmailTaken($command->email);
            }
        }

        $user->updateProfile($command->firstName, $command->lastName, $command->email);
        $this->repository->save($user);
    }
}
