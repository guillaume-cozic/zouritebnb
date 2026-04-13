<?php

declare(strict_types=1);

namespace App\Tests\E2e\SolidarityProject;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\SolidarityProject\Infrastructure\Doctrine\SolidarityProjectEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

abstract class SolidarityProjectApiTestCase extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected function insertSolidarityProject(
        string $title,
        string $description,
        ?string $imageUrl = null,
        string $status = 'active',
        ?\DateTimeImmutable $createdAt = null,
    ): string {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new SolidarityProjectEntity()
            ->setId(Uuid::v7())
            ->setTitle($title)
            ->setDescription($description)
            ->setImageUrl($imageUrl)
            ->setStatus($status)
            ->setCreatedAt($createdAt ?? new \DateTimeImmutable());

        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }
}
