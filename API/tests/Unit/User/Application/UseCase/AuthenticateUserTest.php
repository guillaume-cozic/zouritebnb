<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\UseCase;

use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\User\Infrastructure\FakePasswordHasher;
use App\Tests\Unit\User\Infrastructure\InMemoryUserRepository;
use App\User\Application\UseCase\AuthenticateUser;
use App\User\Domain\Command\AuthenticateUserCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\InvalidCredentialsException;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AuthenticateUserTest extends TestCase
{
    private InMemoryUserRepository $repository;
    private FakePasswordHasher $hasher;
    private AuthenticateUser $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->hasher = new FakePasswordHasher();
        $this->useCase = new AuthenticateUser($this->repository, $this->hasher);
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_authenticate_user_with_valid_credentials(): void
    {
        $user = new User(
            id: Uuid::v7(),
            email: 'jane@example.com',
            hashedPassword: $this->hasher->hash('s3cret'),
            teamId: Uuid::v7(),
        );
        $this->repository->save($user);

        $authenticated = $this->useCase->handle(new AuthenticateUserCommand(
            email: 'jane@example.com',
            password: 's3cret',
        ));

        self::assertSame($user, $authenticated);
    }

    public function test_should_not_authenticate_when_user_does_not_exist(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $this->useCase->handle(new AuthenticateUserCommand(
            email: 'unknown@example.com',
            password: 's3cret',
        ));
    }

    public function test_should_not_authenticate_with_wrong_password(): void
    {
        $user = new User(
            id: Uuid::v7(),
            email: 'jane@example.com',
            hashedPassword: $this->hasher->hash('s3cret'),
            teamId: Uuid::v7(),
        );
        $this->repository->save($user);

        $this->expectException(InvalidCredentialsException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $this->useCase->handle(new AuthenticateUserCommand(
            email: 'jane@example.com',
            password: 'wrong-password',
        ));
    }
}
