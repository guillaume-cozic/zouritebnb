<?php

declare(strict_types=1);

namespace App\Tests\E2e\ActivityPoint;

use PHPUnit\Framework\Attributes\DataProvider;

final class CreateActivityPointTest extends ActivityPointApiTestCase
{
    public function test_should_create_activity_point(): void
    {
        $headers = $this->adminHeaders();

        self::createClient()->request('POST', '/api/admin/activity-points', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Lagune de Mourouk',
                'description' => 'Spot de kitesurf au lagon turquoise.',
                'category' => 'kitesurf',
                'latitude' => -19.7577,
                'longitude' => 63.4499,
                'articleUrl' => '/blog/kitesurf-mourouk',
            ],
        ]);

        self::assertResponseStatusCodeSame(201);

        $response = self::createClient()->request('GET', '/api/activity-points');
        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('Lagune de Mourouk', $members[0]['name']);
        self::assertSame('kitesurf', $members[0]['category']);
    }

    public function test_should_create_activity_point_without_article_url(): void
    {
        $headers = $this->adminHeaders();

        self::createClient()->request('POST', '/api/admin/activity-points', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Grande Montagne',
                'description' => 'Réserve naturelle au sommet de l\'île.',
                'category' => 'nature',
                'latitude' => -19.6833,
                'longitude' => 63.4333,
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('provideInvalidPayloads')]
    public function test_should_return_422_when_payload_is_invalid(array $payload): void
    {
        $headers = $this->adminHeaders();

        self::createClient()->request('POST', '/api/admin/activity-points', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => $payload,
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    /**
     * @return \Generator<string, array{array<string, mixed>}>
     */
    public static function provideInvalidPayloads(): \Generator
    {
        $valid = [
            'name' => 'Lagune de Mourouk',
            'description' => 'Spot de kitesurf au lagon turquoise.',
            'category' => 'kitesurf',
            'latitude' => -19.7577,
            'longitude' => 63.4499,
        ];

        yield 'blank name' => [['name' => ''] + $valid];
        yield 'blank description' => [['description' => '   '] + $valid];
        yield 'invalid category' => [['category' => 'surf'] + $valid];
        yield 'missing latitude' => [array_diff_key($valid, ['latitude' => null])];
        yield 'missing longitude' => [array_diff_key($valid, ['longitude' => null])];
        yield 'latitude below Rodrigues bounds' => [['latitude' => -21.5] + $valid];
        yield 'latitude above Rodrigues bounds' => [['latitude' => -19.0] + $valid];
        yield 'longitude below Rodrigues bounds' => [['longitude' => 57.5] + $valid];
        yield 'longitude above Rodrigues bounds' => [['longitude' => 64.5] + $valid];
        yield 'invalid article url' => [['articleUrl' => 'blog/kitesurf-mourouk'] + $valid];
    }

    public function test_should_return_401_when_unauthenticated(): void
    {
        self::createClient()->request('POST', '/api/admin/activity-points', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Point', 'description' => 'Description', 'category' => 'nature', 'latitude' => -19.7, 'longitude' => 63.4],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_403_when_not_admin(): void
    {
        $this->createAuthUser(email: 'host@example.com');

        self::createClient()->request('POST', '/api/admin/activity-points', [
            'headers' => $this->authHeaders('host@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Point', 'description' => 'Description', 'category' => 'nature', 'latitude' => -19.7, 'longitude' => 63.4],
        ]);

        self::assertResponseStatusCodeSame(403);
    }
}
