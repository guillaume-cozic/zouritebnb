<?php

declare(strict_types=1);

namespace App\Tests\E2e\Geography;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Geography\Infrastructure\Doctrine\LocalityEntity;
use App\Geography\Infrastructure\Doctrine\RegionEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

abstract class GeographyApiTestCase extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected function insertRegion(string $code, string $name): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $id = Uuid::v7();

        $entity = new RegionEntity()
            ->setId($id)
            ->setCode($code)
            ->setName($name);

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }

    protected function insertLocality(string $name, string $regionId): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $id = Uuid::v7();

        $entity = new LocalityEntity()
            ->setId($id)
            ->setName($name)
            ->setRegionId(Uuid::fromString($regionId));

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }
}
