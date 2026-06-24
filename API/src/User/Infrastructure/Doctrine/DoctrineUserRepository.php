<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Doctrine;

use App\User\Domain\Entity\IdentityDocumentType;
use App\User\Domain\Entity\User as DomainUser;
use App\User\Domain\Entity\VerificationStatus;
use App\User\Domain\Port\UserRepository as UserRepositoryPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<UserEntity>
 */
class DoctrineUserRepository extends ServiceEntityRepository implements UserRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserEntity::class);
    }

    public function findById(Uuid $id): ?DomainUser
    {
        $entity = $this->find($id);

        return $entity ? $this->toDomain($entity) : null;
    }

    public function findByEmail(string $email): ?DomainUser
    {
        $entity = $this->findOneBy(['email' => $email]);

        return $entity ? $this->toDomain($entity) : null;
    }

    public function findByTeamId(Uuid $teamId): ?DomainUser
    {
        // A team currently has a single member (the host); the earliest-registered
        // user is treated as the canonical host when displaying a public profile.
        $entity = $this->findOneBy(['teamId' => $teamId], ['email' => 'ASC']);

        return $entity ? $this->toDomain($entity) : null;
    }

    public function save(DomainUser $user): void
    {
        $entity = $this->find($user->getId()) ?? (new UserEntity())->setId($user->getId());
        $entity
            ->setEmail($user->getEmail())
            ->setHashedPassword($user->getHashedPassword())
            ->setTeamId($user->getTeamId())
            ->setFirstName($user->getFirstName())
            ->setLastName($user->getLastName())
            ->setBio($user->getBio())
            ->setAvatarFilename($user->getAvatarFilename())
            ->setVerificationStatus($user->getVerificationStatus()->value)
            ->setIdentityDocumentId($user->getIdentityDocumentId())
            ->setSelfieId($user->getSelfieId())
            ->setDocumentType($user->getDocumentType()?->value)
            ->setVerifiedAt($user->getVerifiedAt());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    private function toDomain(UserEntity $entity): DomainUser
    {
        $documentType = $entity->getDocumentType();

        $user = new DomainUser(
            id: $entity->getId(),
            email: (string) $entity->getEmail(),
            hashedPassword: (string) $entity->getHashedPassword(),
            teamId: $entity->getTeamId(),
            firstName: $entity->getFirstName(),
            lastName: $entity->getLastName(),
            bio: $entity->getBio(),
            avatarFilename: $entity->getAvatarFilename(),
            verificationStatus: VerificationStatus::from($entity->getVerificationStatus()),
            identityDocumentId: $entity->getIdentityDocumentId(),
            selfieId: $entity->getSelfieId(),
            documentType: null !== $documentType ? IdentityDocumentType::from($documentType) : null,
            verifiedAt: $entity->getVerifiedAt(),
        );
        $user->releaseEvents();

        return $user;
    }
}
