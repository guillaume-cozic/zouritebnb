<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\UseCase;

use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use App\Tests\Unit\User\Infrastructure\FakePasswordHasher;
use App\Tests\Unit\User\Infrastructure\InMemoryUserRepository;
use App\User\Application\UseCase\RegisterUser;
use App\User\Domain\Command\RegisterUserCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\UserRegistered;
use App\User\Domain\Exception\UserAlreadyExistsException;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RegisterUserTest extends TestCase
{
    private InMemoryUserRepository $repository;
    private FakePasswordHasher $hasher;
    private InMemoryEventBus $eventBus;
    private RegisterUser $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->hasher = new FakePasswordHasher();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new RegisterUser($this->repository, $this->hasher, $this->eventBus);
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_register_user_and_dispatch_event(): void
    {
        $teamId = Uuid::v7();

        $id = $this->useCase->handle(new RegisterUserCommand(
            email: 'new@example.com',
            password: 's3cret',
            teamId: $teamId,
        ));

        $user = $this->repository->findById(Uuid::fromString($id));
        self::assertNotNull($user);
        self::assertSame($id, $user->getId()->toRfc4122());
        self::assertSame('new@example.com', $user->getEmail());
        self::assertSame('hashed:s3cret', $user->getHashedPassword());
        self::assertTrue($teamId->equals($user->getTeamId()));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserRegistered::class, $events[0]);
        self::assertTrue($user->getId()->equals($events[0]->userId));
        self::assertTrue($teamId->equals($events[0]->teamId));
    }

    public function test_should_not_register_user_with_already_taken_email(): void
    {
        $existing = new User(
            id: Uuid::v7(),
            email: 'taken@example.com',
            hashedPassword: $this->hasher->hash('s3cret'),
            teamId: Uuid::v7(),
        );
        $this->repository->save($existing);

        $this->expectException(UserAlreadyExistsException::class);
        $this->expectExceptionMessage('User with email "taken@example.com" already exists.');

        try {
            $this->useCase->handle(new RegisterUserCommand(
                email: 'taken@example.com',
                password: 's3cret',
                teamId: Uuid::v7(),
            ));
        } finally {
            self::assertCount(0, $this->eventBus->getDispatchedEvents());
        }
    }
}
