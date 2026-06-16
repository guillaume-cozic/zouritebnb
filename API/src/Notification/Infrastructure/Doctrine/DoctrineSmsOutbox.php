<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Doctrine;

use App\Notification\Domain\Entity\OutboxSms;
use App\Notification\Domain\Entity\OutboxStatus;
use App\Notification\Domain\Entity\PhoneNumber;
use App\Notification\Domain\Port\SmsOutbox;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<OutboxSmsEntity>
 */
class DoctrineSmsOutbox extends ServiceEntityRepository implements SmsOutbox
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OutboxSmsEntity::class);
    }

    public function save(OutboxSms $sms): void
    {
        $entity = $this->find($sms->getId()) ?? new OutboxSmsEntity();

        $entity
            ->setId($sms->getId())
            ->setRecipientPhone($sms->getRecipient()->toString())
            ->setText($sms->getText())
            ->setStatus($sms->getStatus()->value)
            ->setAttempts($sms->getAttempts())
            ->setCreatedAt($sms->getCreatedAt())
            ->setLastAttemptAt($sms->getLastAttemptAt())
            ->setError($sms->getError());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function findById(Uuid $id): ?OutboxSms
    {
        $entity = $this->find($id);

        return null !== $entity ? $this->toDomain($entity) : null;
    }

    public function findPending(int $limit): array
    {
        $entities = $this->findBy(
            ['status' => OutboxStatus::Pending->value],
            ['createdAt' => 'ASC'],
            $limit,
        );

        return array_map($this->toDomain(...), $entities);
    }

    private function toDomain(OutboxSmsEntity $entity): OutboxSms
    {
        return OutboxSms::fromState(
            id: $entity->getId(),
            recipient: new PhoneNumber($entity->getRecipientPhone()),
            text: (string) $entity->getText(),
            status: OutboxStatus::from($entity->getStatus()),
            attempts: $entity->getAttempts(),
            createdAt: $entity->getCreatedAt(),
            lastAttemptAt: $entity->getLastAttemptAt(),
            error: $entity->getError(),
        );
    }
}
