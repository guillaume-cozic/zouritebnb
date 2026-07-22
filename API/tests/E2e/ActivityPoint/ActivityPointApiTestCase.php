<?php

declare(strict_types=1);

namespace App\Tests\E2e\ActivityPoint;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\ActivityPoint\Infrastructure\Doctrine\ActivityPointEntity;
use App\Tests\E2e\AuthenticatedClientTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

abstract class ActivityPointApiTestCase extends ApiTestCase
{
    use AuthenticatedClientTrait;

    protected static ?bool $alwaysBootKernel = true;

    protected function insertActivityPoint(
        string $name = 'Lagune de Mourouk',
        string $description = 'Spot de kitesurf au lagon turquoise.',
        string $category = 'kitesurf',
        float $latitude = -19.7577,
        float $longitude = 63.4499,
        ?string $articleUrl = null,
    ): string {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new ActivityPointEntity()
            ->setId(Uuid::v7())
            ->setName($name)
            ->setDescription($description)
            ->setCategory($category)
            ->setLatitude($latitude)
            ->setLongitude($longitude)
            ->setArticleUrl($articleUrl);

        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }

    /**
     * @return array{Authorization: string}
     */
    protected function adminHeaders(string $email = 'admin@example.com'): array
    {
        $this->createAuthUser(email: $email, roles: ['ROLE_ADMIN']);

        return $this->authHeaders($email);
    }
}
