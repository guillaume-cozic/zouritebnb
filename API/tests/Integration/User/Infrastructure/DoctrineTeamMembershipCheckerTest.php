<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Infrastructure;

use App\Shared\Domain\Port\TeamMembershipChecker;
use App\Tests\Integration\RepositoryTestCase;
use App\User\Domain\Entity\User;
use App\User\Domain\Port\UserRepository;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineTeamMembershipCheckerTest extends RepositoryTestCase
{
    private TeamMembershipChecker $checker;
    private UserRepository $users;

    #[Before]
    public function initServices(): void
    {
        $this->checker = self::getContainer()->get(TeamMembershipChecker::class);
        $this->users = self::getContainer()->get(UserRepository::class);
    }

    public function test_should_return_true_when_user_belongs_to_the_team(): void
    {
        $userId = Uuid::v4();
        $teamId = Uuid::v4();
        $this->users->save($this->aUser($userId, $teamId));

        self::assertTrue($this->checker->isMember($userId, $teamId));
    }

    public function test_should_return_false_when_user_belongs_to_another_team(): void
    {
        $userId = Uuid::v4();
        $this->users->save($this->aUser($userId, Uuid::v4()));

        self::assertFalse($this->checker->isMember($userId, Uuid::v4()));
    }

    public function test_should_return_false_when_user_does_not_exist(): void
    {
        self::assertFalse($this->checker->isMember(Uuid::v4(), Uuid::v4()));
    }

    private function aUser(Uuid $id, Uuid $teamId): User
    {
        return new User(
            id: $id,
            email: 'member-'.$id->toRfc4122().'@example.com',
            hashedPassword: 'hashed-password',
            teamId: $teamId,
            firstName: 'John',
            lastName: 'Doe',
        );
    }
}
