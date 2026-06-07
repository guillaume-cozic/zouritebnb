<?php

declare(strict_types=1);

namespace App\Tests\E2e\Team;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Team\Infrastructure\Doctrine\TeamEntity;
use App\Tests\E2e\AuthenticatedClientTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class UpdateTeamFavoriteSolidarityProjectTest extends ApiTestCase
{
    use AuthenticatedClientTrait;

    protected static ?bool $alwaysBootKernel = true;

    private function insertTeam(?string $id = null): string
    {
        $teamId = null !== $id ? Uuid::fromString($id) : Uuid::v7();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new TeamEntity()->setId($teamId);

        $em->persist($entity);
        $em->flush();

        return $teamId->toRfc4122();
    }

    /**
     * @return array{Authorization: string}
     */
    private function memberOf(string $teamId, string $email = 'host@example.com'): array
    {
        $this->createAuthUser(email: $email, teamId: $teamId);

        return $this->authHeaders($email);
    }

    public function test_should_set_favorite_solidarity_project_and_return204(): void
    {
        $teamId = $this->insertTeam();
        $headers = $this->memberOf($teamId);
        $projectId = Uuid::v7()->toRfc4122();

        self::createClient()->request('PATCH', \sprintf('/api/teams/%s/favorite-solidarity-project', $teamId), [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['favoriteSolidarityProjectId' => $projectId],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', \sprintf('/api/teams/%s', $teamId), [
            'headers' => $headers + ['Accept' => 'application/ld+json'],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['favoriteSolidarityProjectId' => $projectId]);
    }

    public function test_should_clear_favorite_solidarity_project_when_null_and_return204(): void
    {
        $teamId = $this->insertTeam();
        $headers = $this->memberOf($teamId);
        $projectId = Uuid::v7()->toRfc4122();

        self::createClient()->request('PATCH', \sprintf('/api/teams/%s/favorite-solidarity-project', $teamId), [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['favoriteSolidarityProjectId' => $projectId],
        ]);
        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('PATCH', \sprintf('/api/teams/%s/favorite-solidarity-project', $teamId), [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['favoriteSolidarityProjectId' => null],
        ]);

        self::assertResponseStatusCodeSame(204);

        $response = self::createClient()->request('GET', \sprintf('/api/teams/%s', $teamId), [
            'headers' => $headers + ['Accept' => 'application/ld+json'],
        ]);

        self::assertResponseIsSuccessful();
        // The serializer skips null values in JSON-LD output, so a cleared
        // favorite is reflected by the absence of the property.
        self::assertArrayNotHasKey('favoriteSolidarityProjectId', $response->toArray());
    }

    public function test_should_clear_favorite_solidarity_project_when_empty_string_and_return204(): void
    {
        $teamId = $this->insertTeam();
        $headers = $this->memberOf($teamId);

        self::createClient()->request('PATCH', \sprintf('/api/teams/%s/favorite-solidarity-project', $teamId), [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['favoriteSolidarityProjectId' => ''],
        ]);

        self::assertResponseStatusCodeSame(204);

        $response = self::createClient()->request('GET', \sprintf('/api/teams/%s', $teamId), [
            'headers' => $headers + ['Accept' => 'application/ld+json'],
        ]);

        self::assertResponseIsSuccessful();
        // The serializer skips null values in JSON-LD output, so a cleared
        // favorite is reflected by the absence of the property.
        self::assertArrayNotHasKey('favoriteSolidarityProjectId', $response->toArray());
    }

    public function test_should_return401_when_not_authenticated(): void
    {
        $teamId = $this->insertTeam();
        $projectId = Uuid::v7()->toRfc4122();

        self::createClient()->request('PATCH', \sprintf('/api/teams/%s/favorite-solidarity-project', $teamId), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['favoriteSolidarityProjectId' => $projectId],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return403_when_updating_another_team(): void
    {
        $teamId = $this->insertTeam();
        $this->createAuthUser(email: 'intruder@example.com', teamId: Uuid::v7()->toRfc4122());
        $projectId = Uuid::v7()->toRfc4122();

        self::createClient()->request('PATCH', \sprintf('/api/teams/%s/favorite-solidarity-project', $teamId), [
            'headers' => $this->authHeaders('intruder@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['favoriteSolidarityProjectId' => $projectId],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return404_when_team_does_not_exist(): void
    {
        $teamId = Uuid::v7()->toRfc4122();
        $headers = $this->memberOf($teamId);
        $projectId = Uuid::v7()->toRfc4122();

        self::createClient()->request('PATCH', \sprintf('/api/teams/%s/favorite-solidarity-project', $teamId), [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['favoriteSolidarityProjectId' => $projectId],
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
