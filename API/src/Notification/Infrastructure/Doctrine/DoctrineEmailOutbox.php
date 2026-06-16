<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Doctrine;

use App\Notification\Domain\Entity\EmailAddress;
use App\Notification\Domain\Entity\EmailStatus;
use App\Notification\Domain\Entity\OutboxEmail;
use App\Notification\Domain\Port\EmailOutbox;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<OutboxEmailEntity>
 */
class DoctrineEmailOutbox extends ServiceEntityRepository implements EmailOutbox
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OutboxEmailEntity::class);
    }

    public function save(OutboxEmail $email): void
    {
        $entity = $this->find($email->getId()) ?? new OutboxEmailEntity();

        $entity
            ->setId($email->getId())
            ->setRecipientEmail($email->getRecipient()->toString())
            ->setRecipientName($email->getRecipientName())
            ->setSubject($email->getSubject())
            ->setHtmlBody($email->getHtmlBody())
            ->setStatus($email->getStatus()->value)
            ->setAttempts($email->getAttempts())
            ->setCreatedAt($email->getCreatedAt())
            ->setLastAttemptAt($email->getLastAttemptAt())
            ->setError($email->getError());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function findById(Uuid $id): ?OutboxEmail
    {
        $entity = $this->find($id);

        return null !== $entity ? $this->toDomain($entity) : null;
    }

    public function findPending(int $limit): array
    {
        $entities = $this->findBy(
            ['status' => EmailStatus::Pending->value],
            ['createdAt' => 'ASC'],
            $limit,
        );

        return array_map($this->toDomain(...), $entities);
    }

    private function toDomain(OutboxEmailEntity $entity): OutboxEmail
    {
        return OutboxEmail::fromState(
            id: $entity->getId(),
            recipient: new EmailAddress($entity->getRecipientEmail()),
            recipientName: $entity->getRecipientName(),
            subject: (string) $entity->getSubject(),
            htmlBody: (string) $entity->getHtmlBody(),
            status: EmailStatus::from($entity->getStatus()),
            attempts: $entity->getAttempts(),
            createdAt: $entity->getCreatedAt(),
            lastAttemptAt: $entity->getLastAttemptAt(),
            error: $entity->getError(),
        );
    }
}
