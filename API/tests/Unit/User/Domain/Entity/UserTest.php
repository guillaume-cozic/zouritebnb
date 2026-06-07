<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\User\Domain\Entity\User;
use App\User\Domain\Event\UserRegistered;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UserTest extends TestCase
{
    public function test_should_create_a_user_with_all_fields(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $teamId = Uuid::fromString('01961e2f-dead-7000-beef-000000000002');

        $user = new User(
            id: $id,
            email: 'john@example.com',
            hashedPassword: 'hashed-secret',
            teamId: $teamId,
            firstName: 'John',
            lastName: 'Doe',
        );

        self::assertSame($id, $user->getId());
        self::assertSame('john@example.com', $user->getEmail());
        self::assertSame('hashed-secret', $user->getHashedPassword());
        self::assertSame($teamId, $user->getTeamId());
        self::assertSame('John', $user->getFirstName());
        self::assertSame('Doe', $user->getLastName());
    }

    public function test_should_create_a_user_without_names(): void
    {
        $user = new User(
            id: Uuid::v7(),
            email: 'jane@example.com',
            hashedPassword: 'hashed-secret',
            teamId: Uuid::v7(),
        );

        self::assertNull($user->getFirstName());
        self::assertNull($user->getLastName());
    }

    public function test_should_not_record_any_event_on_plain_construction(): void
    {
        $user = new User(
            id: Uuid::v7(),
            email: 'jane@example.com',
            hashedPassword: 'hashed-secret',
            teamId: Uuid::v7(),
        );

        self::assertSame([], $user->releaseEvents());
    }

    public function test_should_register_a_user_and_record_event(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $teamId = Uuid::fromString('01961e2f-dead-7000-beef-000000000002');

        $user = User::register(
            id: $id,
            email: 'john@example.com',
            hashedPassword: 'hashed-secret',
            teamId: $teamId,
        );

        self::assertSame($id, $user->getId());
        self::assertSame('john@example.com', $user->getEmail());
        self::assertSame('hashed-secret', $user->getHashedPassword());
        self::assertSame($teamId, $user->getTeamId());
        self::assertNull($user->getFirstName());
        self::assertNull($user->getLastName());

        $events = $user->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserRegistered::class, $events[0]);
        self::assertTrue($id->equals($events[0]->userId));
        self::assertTrue($teamId->equals($events[0]->teamId));
    }

    public function test_should_release_events_only_once(): void
    {
        $user = User::register(
            id: Uuid::v7(),
            email: 'john@example.com',
            hashedPassword: 'hashed-secret',
            teamId: Uuid::v7(),
        );

        self::assertCount(1, $user->releaseEvents());
        self::assertSame([], $user->releaseEvents());
    }

    public function test_should_update_profile(): void
    {
        $user = new User(
            id: Uuid::v7(),
            email: 'old@example.com',
            hashedPassword: 'hashed-secret',
            teamId: Uuid::v7(),
            firstName: 'Old',
            lastName: 'Name',
        );

        $user->updateProfile(firstName: 'New', lastName: 'Surname', email: 'new@example.com');

        self::assertSame('New', $user->getFirstName());
        self::assertSame('Surname', $user->getLastName());
        self::assertSame('new@example.com', $user->getEmail());
    }

    public function test_should_update_profile_with_null_names(): void
    {
        $user = new User(
            id: Uuid::v7(),
            email: 'old@example.com',
            hashedPassword: 'hashed-secret',
            teamId: Uuid::v7(),
            firstName: 'Old',
            lastName: 'Name',
        );

        $user->updateProfile(firstName: null, lastName: null, email: 'new@example.com');

        self::assertNull($user->getFirstName());
        self::assertNull($user->getLastName());
        self::assertSame('new@example.com', $user->getEmail());
    }

    public function test_should_not_change_hashed_password_when_updating_profile(): void
    {
        $user = new User(
            id: Uuid::v7(),
            email: 'old@example.com',
            hashedPassword: 'hashed-secret',
            teamId: Uuid::v7(),
        );

        $user->updateProfile(firstName: 'A', lastName: 'B', email: 'new@example.com');

        self::assertSame('hashed-secret', $user->getHashedPassword());
    }
}
