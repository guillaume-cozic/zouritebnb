<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure;

use App\User\Domain\Entity\User;
use App\User\Domain\Port\UserRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $users = [];

    public function findById(Uuid $id): ?User
    {
        return $this->users[$id->toRfc4122()] ?? null;
    }

    public function findByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->getEmail() === $email) {
                return $user;
            }
        }

        return null;
    }

    public function findByTeamId(Uuid $teamId): ?User
    {
        foreach ($this->users as $user) {
            if ($user->getTeamId()->equals($teamId)) {
                return $user;
            }
        }

        return null;
    }

    public function save(User $user): void
    {
        $this->users[$user->getId()->toRfc4122()] = $user;
    }
}
