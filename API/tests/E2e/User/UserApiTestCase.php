<?php

declare(strict_types=1);

namespace App\Tests\E2e\User;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\User\Infrastructure\Doctrine\UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

abstract class UserApiTestCase extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected function insertUser(
        string $email = 'host@example.com',
        string $plainPassword = 'supersecret',
        ?string $teamId = null,
        ?string $firstName = null,
        ?string $lastName = null,
    ): string {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $id = Uuid::v7();

        $entity = new UserEntity()
            ->setId($id)
            ->setEmail($email)
            ->setHashedPassword(password_hash($plainPassword, \PASSWORD_BCRYPT))
            ->setTeamId(Uuid::fromString($teamId ?? Uuid::v7()->toRfc4122()))
            ->setFirstName($firstName)
            ->setLastName($lastName);

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }
}
