<?php

declare(strict_types=1);

namespace App\Donation\Infrastructure\Doctrine;

use App\Donation\Domain\Entity\Donation;
use App\Donation\Domain\Entity\DonationStatus;
use App\Donation\Domain\Port\DonationRepository as DonationRepositoryPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<DonationEntity>
 */
class DoctrineDonationRepository extends ServiceEntityRepository implements DonationRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DonationEntity::class);
    }

    public function save(Donation $donation): void
    {
        $entity = $this->find($donation->getId()) ?? new DonationEntity();
        $entity
            ->setId($donation->getId())
            ->setSolidarityProjectId($donation->getSolidarityProjectId())
            ->setStripePaymentIntentId($donation->getStripePaymentIntentId())
            ->setStatus($donation->getStatus()->value)
            ->setAmountCents($donation->getAmountCents())
            ->setCurrency($donation->getCurrency())
            ->setCreatedAt($donation->getCreatedAt());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function findById(Uuid $id): ?Donation
    {
        $entity = $this->find($id);

        return $entity ? $this->toDomain($entity) : null;
    }

    public function findByPaymentIntentId(string $paymentIntentId): ?Donation
    {
        $entity = $this->findOneBy(['stripePaymentIntentId' => $paymentIntentId]);

        return $entity ? $this->toDomain($entity) : null;
    }

    private function toDomain(DonationEntity $entity): Donation
    {
        return new Donation(
            id: $entity->getId(),
            solidarityProjectId: $entity->getSolidarityProjectId(),
            stripePaymentIntentId: $entity->getStripePaymentIntentId(),
            status: DonationStatus::from($entity->getStatus()),
            amountCents: $entity->getAmountCents(),
            currency: $entity->getCurrency(),
            createdAt: $entity->getCreatedAt(),
        );
    }
}
