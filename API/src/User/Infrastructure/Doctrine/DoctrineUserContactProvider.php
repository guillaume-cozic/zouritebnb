<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Doctrine;

use App\Shared\Domain\Port\UserContact;
use App\Shared\Domain\Port\UserContactProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineUserContactProvider implements UserContactProvider
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function contactOf(Uuid $userId): ?UserContact
    {
        $user = $this->entityManager->find(UserEntity::class, $userId);

        if (null === $user || null === $user->getEmail()) {
            return null;
        }

        $avatarFilename = $user->getAvatarFilename();

        return new UserContact(
            userId: $userId,
            email: $user->getEmail(),
            firstName: $user->getFirstName(),
            phoneNumber: $user->getPhoneNumber(),
            lastName: $user->getLastName(),
            avatarUrl: null !== $avatarFilename ? '/uploads/photos/'.$avatarFilename : null,
        );
    }
}
