<?php

declare(strict_types=1);

namespace App\Tests\E2e\User;

use Symfony\Component\Uid\Uuid;

final class HostProfileTest extends UserApiTestCase
{
    public function test_should_expose_the_host_public_profile_without_authentication(): void
    {
        $teamId = (string) Uuid::v7();
        $this->insertUser(
            email: 'host@example.com',
            teamId: $teamId,
            firstName: 'Marie',
            lastName: 'Dupont',
        );

        // No auth headers: the marketplace shows the host on public accommodation pages.
        self::createClient()->request('GET', '/api/host-profiles/'.$teamId);

        self::assertResponseIsSuccessful();
        // API Platform omits null fields (bio/avatarUrl) from the JSON output.
        self::assertJsonContains([
            'teamId' => $teamId,
            'firstName' => 'Marie',
            'lastName' => 'Dupont',
        ]);
    }

    public function test_should_return_404_when_the_team_has_no_host(): void
    {
        self::createClient()->request('GET', '/api/host-profiles/'.Uuid::v7());

        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_expose_the_bio_after_the_host_updates_their_profile(): void
    {
        $teamId = (string) Uuid::v7();
        $this->insertUser(email: 'host@example.com', plainPassword: 'supersecret', teamId: $teamId);

        self::createClient()->request('PATCH', '/api/users/profile', [
            'headers' => $this->authHeaders('host@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'firstName' => 'Marie',
                'lastName' => 'Dupont',
                'email' => 'host@example.com',
                'bio' => 'Je loue mon gîte familial depuis 2015.',
            ],
        ]);
        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/host-profiles/'.$teamId);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'firstName' => 'Marie',
            'bio' => 'Je loue mon gîte familial depuis 2015.',
        ]);
    }
}
