<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\UseCase;

use App\Shared\Domain\Event\UserRegistered;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use App\Tests\Unit\User\Infrastructure\FakePasswordHasher;
use App\Tests\Unit\User\Infrastructure\FakeSocialIdentityVerifier;
use App\Tests\Unit\User\Infrastructure\FixedClock;
use App\Tests\Unit\User\Infrastructure\InMemoryUserRepository;
use App\User\Application\UseCase\AuthenticateSocialUser;
use App\User\Domain\Command\AuthenticateSocialUserCommand;
use App\User\Domain\Entity\SocialProvider;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\SocialAuthenticationException;
use App\User\Domain\Port\SocialIdentity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AuthenticateSocialUserTest extends TestCase
{
    private const string NOW = '2026-07-19T10:00:00+00:00';

    public function test_should_register_a_new_user_from_a_verified_identity(): void
    {
        $repository = new InMemoryUserRepository();
        $eventBus = new InMemoryEventBus();
        $useCase = $this->useCase($repository, $eventBus, new SocialIdentity(
            provider: SocialProvider::Google,
            email: 'jane@example.com',
            firstName: 'Jane',
            lastName: 'Doe',
            emailVerified: true,
        ));
        $teamId = Uuid::v7();

        $result = $useCase->handle(new AuthenticateSocialUserCommand(SocialProvider::Google, 'valid-token', $teamId));

        self::assertTrue($result->registered);
        $user = $repository->findByEmail('jane@example.com');
        self::assertNotNull($user);
        self::assertTrue($user->getTeamId()->equals($teamId));
        self::assertSame('Jane', $user->getFirstName());
        self::assertSame('Doe', $user->getLastName());
        self::assertTrue($user->isEmailVerified());
        self::assertSame(self::NOW, $user->getEmailVerifiedAt()?->format(\DateTimeInterface::RFC3339));
        // The random secret is hashed: nothing that could be guessed from outside.
        self::assertStringStartsWith('hashed:', $user->getHashedPassword());

        $events = $eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserRegistered::class, $events[0]);
    }

    public function test_should_not_mark_email_verified_when_provider_does_not_attest_it(): void
    {
        $repository = new InMemoryUserRepository();
        $useCase = $this->useCase($repository, new InMemoryEventBus(), new SocialIdentity(
            provider: SocialProvider::Facebook,
            email: 'jane@example.com',
            emailVerified: false,
        ));

        $useCase->handle(new AuthenticateSocialUserCommand(SocialProvider::Facebook, 'valid-token', Uuid::v7()));

        self::assertFalse($repository->findByEmail('jane@example.com')->isEmailVerified());
    }

    public function test_should_log_in_existing_user_without_registering(): void
    {
        $repository = new InMemoryUserRepository();
        $eventBus = new InMemoryEventBus();
        $existingTeamId = Uuid::v7();
        $existing = new User(
            id: Uuid::v7(),
            email: 'jane@example.com',
            hashedPassword: 'hashed:classic-password',
            teamId: $existingTeamId,
        );
        $repository->save($existing);
        $useCase = $this->useCase($repository, $eventBus, new SocialIdentity(
            provider: SocialProvider::Google,
            email: 'jane@example.com',
        ));

        $result = $useCase->handle(new AuthenticateSocialUserCommand(SocialProvider::Google, 'valid-token', Uuid::v7()));

        self::assertFalse($result->registered);
        self::assertTrue($result->user->getId()->equals($existing->getId()));
        self::assertTrue($result->user->getTeamId()->equals($existingTeamId));
        // The existing password stays untouched: classic login keeps working.
        self::assertSame('hashed:classic-password', $repository->findByEmail('jane@example.com')->getHashedPassword());
        self::assertSame([], $eventBus->getDispatchedEvents());
    }

    public function test_should_propagate_verification_failure(): void
    {
        $useCase = $this->useCase(new InMemoryUserRepository(), new InMemoryEventBus(), new SocialIdentity(
            provider: SocialProvider::Google,
            email: 'jane@example.com',
        ));

        $this->expectException(SocialAuthenticationException::class);

        $useCase->handle(new AuthenticateSocialUserCommand(SocialProvider::Google, 'wrong-token', Uuid::v7()));
    }

    private function useCase(
        InMemoryUserRepository $repository,
        InMemoryEventBus $eventBus,
        SocialIdentity $identity,
    ): AuthenticateSocialUser {
        return new AuthenticateSocialUser(
            verifier: new FakeSocialIdentityVerifier('valid-token', $identity),
            repository: $repository,
            hasher: new FakePasswordHasher(),
            eventBus: $eventBus,
            clock: new FixedClock(new \DateTimeImmutable(self::NOW)),
        );
    }
}
