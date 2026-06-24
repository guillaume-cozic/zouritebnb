<?php

declare(strict_types=1);

namespace App\User\Domain\Port;

use App\User\Domain\Entity\User;
use Symfony\Component\Uid\Uuid;

interface UserRepository
{
    public function findById(Uuid $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findByTeamId(Uuid $teamId): ?User;

    public function save(User $user): void;
}
