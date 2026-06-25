<?php

declare(strict_types=1);

namespace App\Tests\E2e\Wishlist;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\Tests\E2e\AuthenticatedClientTrait;
use App\Wishlist\Infrastructure\ApiPlatform\WishlistOwnerResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class WishlistApiTest extends ApiTestCase
{
    use AuthenticatedClientTrait;

    protected static ?bool $alwaysBootKernel = true;

    private function insertAccommodation(string $title = 'Loft test'): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $id = Uuid::v7();
        $entity = new AccommodationEntity()
            ->setId($id)
            ->setTitle($title)
            ->setDescription('Description test')
            ->setPrice(120.0)
            ->setStatus('published')
            ->setCity('Saint-Denis')
            ->setCountry('La Réunion')
            ->setTeamId(Uuid::v7());

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }

    /** @return array{X-Wishlist-Id: string} */
    private function correlationHeader(?string $id = null): array
    {
        return [WishlistOwnerResolver::CORRELATION_HEADER => $id ?? Uuid::v7()->toRfc4122()];
    }

    public function test_anonymous_visitor_can_add_and_list_via_correlation_header(): void
    {
        $accommodationId = $this->insertAccommodation();
        $header = $this->correlationHeader();

        self::createClient()->request('POST', '/api/wishlist', [
            'headers' => $header + ['Content-Type' => 'application/ld+json'],
            'json' => ['accommodationId' => $accommodationId],
        ]);
        self::assertResponseStatusCodeSame(201);

        $response = self::createClient()->request('GET', '/api/wishlist', ['headers' => $header]);
        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame($accommodationId, $members[0]['accommodationId']);
        self::assertSame('Loft test', $members[0]['title']);
    }

    public function test_add_is_idempotent(): void
    {
        $accommodationId = $this->insertAccommodation();
        $header = $this->correlationHeader();

        foreach ([0, 1] as $_) {
            self::createClient()->request('POST', '/api/wishlist', [
                'headers' => $header + ['Content-Type' => 'application/ld+json'],
                'json' => ['accommodationId' => $accommodationId],
            ]);
        }

        $response = self::createClient()->request('GET', '/api/wishlist', ['headers' => $header]);
        self::assertCount(1, $response->toArray()['member']);
    }

    public function test_anonymous_lists_are_isolated_by_correlation_id(): void
    {
        $accommodationId = $this->insertAccommodation();
        $mine = $this->correlationHeader();
        self::createClient()->request('POST', '/api/wishlist', [
            'headers' => $mine + ['Content-Type' => 'application/ld+json'],
            'json' => ['accommodationId' => $accommodationId],
        ]);

        // A different correlation id sees an empty wishlist.
        $response = self::createClient()->request('GET', '/api/wishlist', ['headers' => $this->correlationHeader()]);
        self::assertCount(0, $response->toArray()['member']);
    }

    public function test_anonymous_can_remove(): void
    {
        $accommodationId = $this->insertAccommodation();
        $header = $this->correlationHeader();
        self::createClient()->request('POST', '/api/wishlist', [
            'headers' => $header + ['Content-Type' => 'application/ld+json'],
            'json' => ['accommodationId' => $accommodationId],
        ]);

        self::createClient()->request('DELETE', '/api/wishlist/'.$accommodationId, ['headers' => $header]);
        self::assertResponseStatusCodeSame(204);

        $response = self::createClient()->request('GET', '/api/wishlist', ['headers' => $header]);
        self::assertCount(0, $response->toArray()['member']);
    }

    public function test_post_without_correlation_header_for_anonymous_returns_400(): void
    {
        $accommodationId = $this->insertAccommodation();

        self::createClient()->request('POST', '/api/wishlist', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['accommodationId' => $accommodationId],
        ]);

        self::assertResponseStatusCodeSame(400);
    }

    public function test_post_unknown_accommodation_returns_422(): void
    {
        self::createClient()->request('POST', '/api/wishlist', [
            'headers' => $this->correlationHeader() + ['Content-Type' => 'application/ld+json'],
            'json' => ['accommodationId' => Uuid::v7()->toRfc4122()],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_authenticated_user_has_own_wishlist(): void
    {
        $accommodationId = $this->insertAccommodation();
        $this->createAuthUser(email: 'wishlist-user@example.com', teamId: Uuid::v7()->toRfc4122());
        $auth = $this->authHeaders('wishlist-user@example.com');

        self::createClient()->request('POST', '/api/wishlist', [
            'headers' => $auth + ['Content-Type' => 'application/ld+json'],
            'json' => ['accommodationId' => $accommodationId],
        ]);
        self::assertResponseStatusCodeSame(201);

        $response = self::createClient()->request('GET', '/api/wishlist', ['headers' => $auth]);
        self::assertCount(1, $response->toArray()['member']);
    }

    public function test_merge_moves_anonymous_wishlist_into_account(): void
    {
        $accommodationId = $this->insertAccommodation();
        $header = $this->correlationHeader();

        // Anonymous saves an accommodation.
        self::createClient()->request('POST', '/api/wishlist', [
            'headers' => $header + ['Content-Type' => 'application/ld+json'],
            'json' => ['accommodationId' => $accommodationId],
        ]);

        // User signs in and merges.
        $this->createAuthUser(email: 'merge-user@example.com', teamId: Uuid::v7()->toRfc4122());
        $auth = $this->authHeaders('merge-user@example.com');
        self::createClient()->request('POST', '/api/wishlist/merge', [
            'headers' => $auth + $header + ['Content-Type' => 'application/ld+json'],
            'json' => [],
        ]);
        self::assertResponseStatusCodeSame(204);

        // The account now owns the saved accommodation.
        $response = self::createClient()->request('GET', '/api/wishlist', ['headers' => $auth]);
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame($accommodationId, $members[0]['accommodationId']);
    }

    public function test_merge_requires_authentication(): void
    {
        self::createClient()->request('POST', '/api/wishlist/merge', [
            'headers' => $this->correlationHeader() + ['Content-Type' => 'application/ld+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(401);
    }
}
