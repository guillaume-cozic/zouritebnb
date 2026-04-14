<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\Doctrine;

use App\Team\Domain\Entity\InvitationStatus;
use App\Team\Domain\Entity\TeamInvitation as DomainTeamInvitation;
use App\Team\Domain\Port\TeamInvitationRepository as TeamInvitationRepositoryPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<TeamInvitationEntity>
 */
class DoctrineTeamInvitationRepository extends ServiceEntityRepository implements TeamInvitationRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamInvitationEntity::class);
    }

    public function save(DomainTeamInvitation $invitation): void
    {
        $entity = $this->find($invitation->getId()) ?? (new TeamInvitationEntity())->setId($invitation->getId());
        $entity
            ->setTeamId($invitation->getTeamId())
            ->setEmail($invitation->getEmail())
            ->setStatus($invitation->getStatus()->value)
            ->setCreatedAt($invitation->getCreatedAt());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function findById(Uuid $id): ?DomainTeamInvitation
    {
        $entity = $this->find($id);

        return $entity ? $this->toDomain($entity) : null;
    }

    public function findPendingByTeam(Uuid $teamId): array
    {
        $entities = $this->findBy([
            'teamId' => $teamId,
            'status' => InvitationStatus::Pending->value,
        ], ['createdAt' => 'ASC']);

        return array_map($this->toDomain(...), $entities);
    }

    private function toDomain(TeamInvitationEntity $entity): DomainTeamInvitation
    {
        return new DomainTeamInvitation(
            id: $entity->getId(),
            teamId: $entity->getTeamId(),
            email: $entity->getEmail(),
            status: InvitationStatus::from($entity->getStatus()),
            createdAt: $entity->getCreatedAt(),
        );
    }
}
