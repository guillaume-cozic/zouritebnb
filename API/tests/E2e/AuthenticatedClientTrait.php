<?php

declare(strict_types=1);

namespace App\Tests\E2e;

use App\User\Infrastructure\Doctrine\UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Helper for E2E tests that need to call authenticated (JWT-protected) endpoints.
 *
 * Requires the host TestCase to extend ApiPlatform\Symfony\Bundle\Test\ApiTestCase
 * (so that self::getContainer() is available and the kernel is booted).
 *
 * Typical usage:
 *
 *     use App\Tests\E2e\AuthenticatedClientTrait;
 *
 *     final class SomethingTest extends ApiTestCase
 *     {
 *         use AuthenticatedClientTrait;
 *
 *         public function test_it_does_something(): void
 *         {
 *             $userId = $this->createAuthUser(email: 'host@example.com'); // persists a UserEntity
 *             $headers = $this->authHeaders('host@example.com');          // ['Authorization' => 'Bearer <jwt>']
 *
 *             self::createClient()->request('PATCH', '/api/users/profile', [
 *                 'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
 *                 'json' => [...],
 *             ]);
 *         }
 *     }
 *
 * API:
 * - createAuthUser(email?, plainPassword?, teamId?, firstName?, lastName?): string
 *       Persists a UserEntity and returns its UUID (RFC4122 string).
 * - tokenFor(string $email): string
 *       Returns a raw JWT for an already-persisted user identified by email.
 * - authHeaders(string $email): array{Authorization: string}
 *       Returns the Authorization header array for an already-persisted user.
 * - authHeadersForEntity(UserEntity $user): array{Authorization: string}
 *       Returns the Authorization header array for a given UserEntity instance.
 */
trait AuthenticatedClientTrait
{
    /**
     * Persists a security user and returns its UUID (RFC4122).
     *
     * @param list<string> $roles additional roles (e.g. ['ROLE_ADMIN']); ROLE_USER is always granted
     */
    protected function createAuthUser(
        string $email = 'host@example.com',
        string $plainPassword = 'supersecret',
        ?string $teamId = null,
        ?string $firstName = null,
        ?string $lastName = null,
        array $roles = [],
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
            ->setLastName($lastName)
            ->setRoles($roles);

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }

    /**
     * Raw JWT for an already-persisted user identified by email.
     */
    protected function tokenFor(string $email): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->getRepository(UserEntity::class)->findOneBy(['email' => $email]);

        if (!$user instanceof UserEntity) {
            throw new \RuntimeException(\sprintf('No fixture user found with email "%s". Create it first with createAuthUser().', $email));
        }

        /** @var JWTTokenManagerInterface $tokenManager */
        $tokenManager = self::getContainer()->get(JWTTokenManagerInterface::class);

        return $tokenManager->create($user);
    }

    /**
     * Authorization header for an already-persisted user identified by email.
     *
     * @return array{Authorization: string}
     */
    protected function authHeaders(string $email): array
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->getRepository(UserEntity::class)->findOneBy(['email' => $email]);

        if (!$user instanceof UserEntity) {
            throw new \RuntimeException(\sprintf('No fixture user found with email "%s". Create it first with createAuthUser().', $email));
        }

        return $this->authHeadersForEntity($user);
    }

    /**
     * Authorization header for a given UserEntity instance.
     *
     * @return array{Authorization: string}
     */
    protected function authHeadersForEntity(UserEntity $user): array
    {
        /** @var JWTTokenManagerInterface $tokenManager */
        $tokenManager = self::getContainer()->get(JWTTokenManagerInterface::class);

        return ['Authorization' => 'Bearer '.$tokenManager->create($user)];
    }
}
