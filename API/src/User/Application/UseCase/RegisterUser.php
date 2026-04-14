<?php

declare(strict_types=1);

namespace App\User\Application\UseCase;

use App\Shared\Domain\Port\EventBus;
use App\User\Domain\Command\RegisterUserCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Port\PasswordHasher;
use App\User\Domain\Port\UserRepository;
use Symfony\Component\Uid\Uuid;

final readonly class RegisterUser
{
    public function __construct(
        private UserRepository $repository,
        private PasswordHasher $hasher,
        private EventBus $eventBus,
    ) {
    }

    public function handle(RegisterUserCommand $command): string
    {
        if (null !== $this->repository->findByEmail($command->email)) {
            throw UserAlreadyExistsException::becauseEmailTaken($command->email);
        }

        $user = User::register(
            id: Uuid::v7(),
            email: $command->email,
            hashedPassword: $this->hasher->hash($command->password),
            teamId: $command->teamId,
        );

        $this->repository->save($user);
        $this->eventBus->dispatch($user->releaseEvents());

        return $user->getId()->toRfc4122();
    }
}
