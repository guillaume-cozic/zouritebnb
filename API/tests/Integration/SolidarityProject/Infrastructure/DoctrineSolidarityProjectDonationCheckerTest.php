<?php

declare(strict_types=1);

namespace App\Tests\Integration\SolidarityProject\Infrastructure;

use App\Shared\Domain\Port\SolidarityProjectDonationChecker;
use App\SolidarityProject\Infrastructure\Doctrine\DoctrineSolidarityProjectDonationChecker;
use App\SolidarityProject\Infrastructure\Doctrine\SolidarityProjectEntity;
use App\Tests\Integration\RepositoryTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineSolidarityProjectDonationCheckerTest extends RepositoryTestCase
{
    private SolidarityProjectDonationChecker $checker;
    private EntityManagerInterface $entityManager;

    #[Before]
    public function initServices(): void
    {
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        // The port alias is inlined into its single consumer at container compile
        // time, so the adapter is built directly against the real EntityManager.
        $this->checker = new DoctrineSolidarityProjectDonationChecker(entityManager: $this->entityManager);
    }

    public function test_should_return_true_when_project_is_active(): void
    {
        $projectId = Uuid::v4();
        $this->persistProject($projectId, 'active');

        self::assertTrue($this->checker->isActive($projectId));
    }

    public function test_should_return_false_when_project_is_closed(): void
    {
        $projectId = Uuid::v4();
        $this->persistProject($projectId, 'closed');

        self::assertFalse($this->checker->isActive($projectId));
    }

    public function test_should_return_false_when_project_does_not_exist(): void
    {
        self::assertFalse($this->checker->isActive(Uuid::v4()));
    }

    private function persistProject(Uuid $id, string $status): void
    {
        $project = new SolidarityProjectEntity();
        $project
            ->setId($id)
            ->setStatus($status)
            ->setCreatedAt(new \DateTimeImmutable('2026-07-01 00:00:00'))
            ->setTranslations([
                'fr' => [
                    'title' => 'Projet solidaire',
                    'description' => 'Description du projet.',
                    'keyFigures' => [],
                ],
            ]);

        $this->entityManager->persist($project);
        $this->entityManager->flush();
    }
}
