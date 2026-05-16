<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Doctrine;

use App\Shared\Domain\Port\UserTeamProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineUserTeamProvider implements UserTeamProvider
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function teamIdOf(Uuid $userId): ?Uuid
    {
        $user = $this->entityManager->find(UserEntity::class, $userId);

        return $user?->getTeamId();
    }
}
