<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\Doctrine;

use App\Team\Domain\Entity\BankAccount;
use App\Team\Domain\Entity\Bic;
use App\Team\Domain\Entity\Iban;
use App\Team\Domain\Entity\Team as DomainTeam;
use App\Team\Domain\Port\TeamRepository as TeamRepositoryPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<TeamEntity>
 */
class DoctrineTeamRepository extends ServiceEntityRepository implements TeamRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamEntity::class);
    }

    public function findById(Uuid $id): ?DomainTeam
    {
        $entity = $this->find($id);

        return $entity ? $this->toDomain($entity) : null;
    }

    public function save(DomainTeam $team): void
    {
        $entity = $this->find($team->getId()) ?? (new TeamEntity())->setId($team->getId());
        $entity->setFavoriteSolidarityProjectId($team->getFavoriteSolidarityProjectId());

        $bankAccount = $team->getBankAccount();
        $entity->setIban($bankAccount?->iban->value());
        $entity->setBic($bankAccount?->bic?->value());
        $entity->setBankAccountHolderName($bankAccount?->holderName);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    private function toDomain(TeamEntity $entity): DomainTeam
    {
        $bankAccount = null;
        $iban = $entity->getIban();
        $holderName = $entity->getBankAccountHolderName();
        if (null !== $iban && null !== $holderName) {
            $bic = $entity->getBic();
            $bankAccount = new BankAccount(
                iban: new Iban($iban),
                bic: null !== $bic ? new Bic($bic) : null,
                holderName: $holderName,
            );
        }

        $team = new DomainTeam(
            id: $entity->getId(),
            favoriteSolidarityProjectId: $entity->getFavoriteSolidarityProjectId(),
            bankAccount: $bankAccount,
        );
        $team->releaseEvents();

        return $team;
    }
}
