<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Doctrine;

use App\Shared\Domain\Port\TeamContactProvider;
use App\Shared\Domain\Port\UserContact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineTeamContactProvider implements TeamContactProvider
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function contactsOf(Uuid $teamId): array
    {
        $users = $this->entityManager
            ->getRepository(UserEntity::class)
            ->findBy(['teamId' => $teamId]);

        return array_values(array_map(
            static fn (UserEntity $user): UserContact => new UserContact(
                userId: $user->getId(),
                email: (string) $user->getEmail(),
                firstName: $user->getFirstName(),
            ),
            $users,
        ));
    }
}
