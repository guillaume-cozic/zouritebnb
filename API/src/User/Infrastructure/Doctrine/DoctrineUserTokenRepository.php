<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Doctrine;

use App\User\Domain\Entity\UserToken as DomainUserToken;
use App\User\Domain\Entity\UserTokenPurpose;
use App\User\Domain\Port\UserTokenRepository as UserTokenRepositoryPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<UserTokenEntity>
 */
class DoctrineUserTokenRepository extends ServiceEntityRepository implements UserTokenRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserTokenEntity::class);
    }

    public function save(DomainUserToken $token): void
    {
        $entity = $this->find($token->getId()) ?? (new UserTokenEntity())->setId($token->getId());
        $entity
            ->setUserId($token->getUserId())
            ->setPurpose($token->getPurpose()->value)
            ->setHashedToken($token->getHashedToken())
            ->setExpiresAt($token->getExpiresAt())
            ->setUsedAt($token->getUsedAt());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function findByHash(string $hashedToken): ?DomainUserToken
    {
        $entity = $this->findOneBy(['hashedToken' => $hashedToken]);

        return $entity ? $this->toDomain($entity) : null;
    }

    public function deleteUsableFor(Uuid $userId, UserTokenPurpose $purpose): void
    {
        $this->getEntityManager()
            ->createQuery(
                'DELETE FROM '.UserTokenEntity::class.' t
                 WHERE t.userId = :userId AND t.purpose = :purpose AND t.usedAt IS NULL'
            )
            ->setParameter('userId', $userId, 'uuid')
            ->setParameter('purpose', $purpose->value)
            ->execute();
    }

    private function toDomain(UserTokenEntity $entity): DomainUserToken
    {
        return new DomainUserToken(
            id: $entity->getId(),
            userId: $entity->getUserId(),
            purpose: UserTokenPurpose::from((string) $entity->getPurpose()),
            hashedToken: (string) $entity->getHashedToken(),
            expiresAt: $entity->getExpiresAt(),
            usedAt: $entity->getUsedAt(),
        );
    }
}
