<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Doctrine;

use App\Shared\Domain\Port\TeamMembershipChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineTeamMembershipChecker implements TeamMembershipChecker
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function isMember(Uuid $userId, Uuid $teamId): bool
    {
        $user = $this->entityManager->find(UserEntity::class, $userId);

        if (null === $user) {
            return false;
        }

        return $user->getTeamId()?->equals($teamId) ?? false;
    }
}
