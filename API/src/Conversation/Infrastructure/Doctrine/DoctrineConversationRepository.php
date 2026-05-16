<?php

declare(strict_types=1);

namespace App\Conversation\Infrastructure\Doctrine;

use App\Conversation\Domain\Entity\Conversation as DomainConversation;
use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Entity\Message as DomainMessage;
use App\Conversation\Domain\Entity\MessageBody;
use App\Conversation\Domain\Entity\MessageId;
use App\Conversation\Domain\Port\ConversationRepository as ConversationRepositoryPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ConversationEntity>
 */
class DoctrineConversationRepository extends ServiceEntityRepository implements ConversationRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConversationEntity::class);
    }

    public function save(DomainConversation $conversation): void
    {
        $id = $conversation->getId()->toUuid();
        $entity = $this->find($id) ?? new ConversationEntity();

        $entity
            ->setId($id)
            ->setReservationId($conversation->getReservationId())
            ->setAccommodationId($conversation->getAccommodationId())
            ->setTeamId($conversation->getTeamId())
            ->setGuestUserId($conversation->getGuestUserId())
            ->setCreatedAt($conversation->getCreatedAt());

        $existingIds = [];
        foreach ($entity->getMessages() as $existing) {
            $existingIds[$existing->getId()->toRfc4122()] = true;
        }

        foreach ($conversation->getMessages() as $message) {
            $key = $message->getId()->toString();
            if (isset($existingIds[$key])) {
                continue;
            }

            $messageEntity = new MessageEntity()
                ->setId($message->getId()->toUuid())
                ->setBody($message->getBody()->toString())
                ->setAuthorUserId($message->getAuthorUserId())
                ->setSentAt($message->getSentAt())
                ->setIsSystem($message->isSystem());

            $entity->addMessage($messageEntity);
        }

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function ofId(ConversationId $id): ?DomainConversation
    {
        $entity = $this->find($id->toUuid());

        return $entity ? $this->toDomain($entity) : null;
    }

    public function ofReservationId(Uuid $reservationId): ?DomainConversation
    {
        $entity = $this->findOneBy(['reservationId' => $reservationId]);

        return $entity ? $this->toDomain($entity) : null;
    }

    public function listForGuestUser(Uuid $userId): array
    {
        $entities = $this->createQueryBuilder('c')
            ->andWhere('c.guestUserId = :userId')
            ->setParameter('userId', $userId, UuidType::NAME)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map($this->toDomain(...), $entities);
    }

    public function listForTeam(Uuid $teamId): array
    {
        $entities = $this->createQueryBuilder('c')
            ->andWhere('c.teamId = :teamId')
            ->setParameter('teamId', $teamId, UuidType::NAME)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map($this->toDomain(...), $entities);
    }

    private function toDomain(ConversationEntity $entity): DomainConversation
    {
        $messages = [];
        foreach ($entity->getMessages() as $messageEntity) {
            $messages[] = new DomainMessage(
                id: new MessageId($messageEntity->getId()),
                body: new MessageBody($messageEntity->getBody()),
                authorUserId: $messageEntity->getAuthorUserId(),
                sentAt: $messageEntity->getSentAt(),
                isSystem: $messageEntity->isSystem(),
            );
        }

        return new DomainConversation(
            id: new ConversationId($entity->getId()),
            reservationId: $entity->getReservationId(),
            accommodationId: $entity->getAccommodationId(),
            teamId: $entity->getTeamId(),
            guestUserId: $entity->getGuestUserId(),
            createdAt: $entity->getCreatedAt(),
            messages: $messages,
        );
    }
}
