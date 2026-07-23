<?php

declare(strict_types=1);

namespace App\Contact\Infrastructure\Doctrine;

use App\Contact\Domain\Entity\ContactMessage as DomainContactMessage;
use App\Contact\Domain\Port\ContactMessageRepository as ContactMessageRepositoryPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ContactMessageEntity>
 */
class DoctrineContactMessageRepository extends ServiceEntityRepository implements ContactMessageRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactMessageEntity::class);
    }

    public function save(DomainContactMessage $message): void
    {
        $id = $message->getId();
        $entity = $this->find($id) ?? new ContactMessageEntity();

        $entity
            ->setId($id)
            ->setName($message->getName())
            ->setEmail($message->getEmail())
            ->setSubject($message->getSubject())
            ->setMessage($message->getMessage())
            ->setSentAt($message->getSentAt());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function findById(Uuid $id): ?DomainContactMessage
    {
        $entity = $this->find($id);

        return $entity ? $this->toDomain($entity) : null;
    }

    private function toDomain(ContactMessageEntity $entity): DomainContactMessage
    {
        return new DomainContactMessage(
            id: $entity->getId(),
            name: $entity->getName(),
            email: $entity->getEmail(),
            subject: $entity->getSubject(),
            message: $entity->getMessage(),
            sentAt: $entity->getSentAt(),
        );
    }
}
