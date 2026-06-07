<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\UseCase;

use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\User\Infrastructure\InMemoryUserRepository;
use App\User\Application\UseCase\UpdateUserProfile;
use App\User\Domain\Command\UpdateUserProfileCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Exception\UserNotFoundException;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateUserProfileTest extends TestCase
{
    private InMemoryUserRepository $repository;
    private UpdateUserProfile $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->useCase = new UpdateUserProfile($this->repository);
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_update_user_profile(): void
    {
        $id = Uuid::v7();
        $this->repository->save(new User(
            id: $id,
            email: 'old@example.com',
            hashedPassword: 'hashed:s3cret',
            teamId: Uuid::v7(),
        ));

        $this->useCase->handle(new UpdateUserProfileCommand(
            id: $id,
            firstName: 'Jane',
            lastName: 'Doe',
            email: 'new@example.com',
        ));

        $user = $this->repository->findById($id);
        self::assertNotNull($user);
        self::assertSame('Jane', $user->getFirstName());
        self::assertSame('Doe', $user->getLastName());
        self::assertSame('new@example.com', $user->getEmail());
    }

    public function test_should_update_profile_keeping_same_email(): void
    {
        $id = Uuid::v7();
        $this->repository->save(new User(
            id: $id,
            email: 'same@example.com',
            hashedPassword: 'hashed:s3cret',
            teamId: Uuid::v7(),
        ));

        $this->useCase->handle(new UpdateUserProfileCommand(
            id: $id,
            firstName: 'John',
            lastName: null,
            email: 'same@example.com',
        ));

        $user = $this->repository->findById($id);
        self::assertNotNull($user);
        self::assertSame('John', $user->getFirstName());
        self::assertNull($user->getLastName());
        self::assertSame('same@example.com', $user->getEmail());
    }

    public function test_should_not_update_profile_of_unknown_user(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000999');

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage(\sprintf('User "%s" not found.', $id->toRfc4122()));

        $this->useCase->handle(new UpdateUserProfileCommand(
            id: $id,
            firstName: 'Jane',
            lastName: 'Doe',
            email: 'new@example.com',
        ));
    }

    public function test_should_not_update_profile_to_an_email_used_by_another_user(): void
    {
        $id = Uuid::v7();
        $this->repository->save(new User(
            id: $id,
            email: 'me@example.com',
            hashedPassword: 'hashed:s3cret',
            teamId: Uuid::v7(),
        ));
        $this->repository->save(new User(
            id: Uuid::v7(),
            email: 'taken@example.com',
            hashedPassword: 'hashed:s3cret',
            teamId: Uuid::v7(),
        ));

        $this->expectException(UserAlreadyExistsException::class);
        $this->expectExceptionMessage('User with email "taken@example.com" already exists.');

        $this->useCase->handle(new UpdateUserProfileCommand(
            id: $id,
            firstName: 'Jane',
            lastName: 'Doe',
            email: 'taken@example.com',
        ));
    }
}
