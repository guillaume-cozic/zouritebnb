<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\Doctrine;

use App\Shared\Domain\Port\SolidarityProjectDonationChecker;
use App\SolidarityProject\Domain\Entity\SolidarityProject;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineSolidarityProjectDonationChecker implements SolidarityProjectDonationChecker
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function isActive(Uuid $solidarityProjectId): bool
    {
        $project = $this->entityManager->find(SolidarityProjectEntity::class, $solidarityProjectId);

        if (null === $project) {
            return false;
        }

        return SolidarityProject::STATUS_ACTIVE === $project->getStatus();
    }
}
