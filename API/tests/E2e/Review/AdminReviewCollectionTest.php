<?php

declare(strict_types=1);

namespace App\Tests\E2e\Review;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\Review\Infrastructure\Doctrine\ReviewEntity;
use App\Tests\E2e\AuthenticatedClientTrait;
use Symfony\Component\Uid\Uuid;

final class AdminReviewCollectionTest extends ReviewApiTestCase
{
    use AuthenticatedClientTrait;

    public function test_should_list_all_reviews_as_admin(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);
        $authorId = $this->createAuthUser(email: 'marie@example.com', firstName: 'Marie', lastName: 'Dupont');
        $guestId = $this->createAuthUser(email: 'guest@example.com');
        $accommodationId = $this->insertAccommodation('Villa du lagon');

        $latestId = $this->insertReview(
            type: 'accommodation',
            authorUserId: $authorId,
            subjectAccommodationId: $accommodationId,
            rating: 5,
            comment: 'Séjour parfait, logement propre et bien situé, hôte très réactif.',
            createdAt: '2026-05-12T14:30:00+00:00',
        );
        $oldestId = $this->insertReview(
            type: 'guest',
            authorUserId: $authorId,
            subjectUserId: $guestId,
            rating: 4,
            comment: 'Voyageur très agréable, communication facile et logement rendu impeccable.',
            createdAt: '2026-04-01T10:00:00+00:00',
        );

        $response = self::createClient()->request('GET', '/api/admin/reviews', [
            'headers' => $this->authHeaders('admin@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
        self::assertJsonContains([
            'member' => [
                [
                    'id' => $latestId,
                    'type' => 'accommodation',
                    'rating' => 5,
                    'comment' => 'Séjour parfait, logement propre et bien situé, hôte très réactif.',
                    'createdAt' => '2026-05-12T14:30:00+00:00',
                    'authorUserId' => $authorId,
                    'authorName' => 'Marie Dupont',
                    'subjectAccommodationId' => $accommodationId,
                    'subjectAccommodationTitle' => 'Villa du lagon',
                    'subjectUserId' => null,
                    'subjectUserName' => null,
                ],
                [
                    'id' => $oldestId,
                    'type' => 'guest',
                    'rating' => 4,
                    'authorName' => 'Marie Dupont',
                    'subjectAccommodationId' => null,
                    'subjectAccommodationTitle' => null,
                    'subjectUserId' => $guestId,
                    'subjectUserName' => 'guest@example.com',
                ],
            ],
        ]);
    }

    public function test_should_return_403_when_not_admin(): void
    {
        $this->createAuthUser(email: 'host@example.com');

        self::createClient()->request('GET', '/api/admin/reviews', [
            'headers' => $this->authHeaders('host@example.com'),
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        self::createClient()->request('GET', '/api/admin/reviews');

        self::assertResponseStatusCodeSame(401);
    }

    private function insertAccommodation(string $title): string
    {
        $em = $this->entityManager();

        $entity = new AccommodationEntity()
            ->setId(Uuid::v7())
            ->setTitle($title)
            ->setDescription('Une description suffisante pour la fixture.')
            ->setPrice(100.0)
            ->setStatus('published')
            ->setTeamId(Uuid::fromString(self::DEFAULT_TEAM_UUID));

        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }

    private function insertReview(
        string $type,
        string $authorUserId,
        int $rating,
        string $comment,
        string $createdAt,
        ?string $subjectAccommodationId = null,
        ?string $subjectUserId = null,
    ): string {
        $em = $this->entityManager();

        $id = Uuid::v7();
        $entity = new ReviewEntity()
            ->setId($id)
            ->setType($type)
            ->setReservationId(Uuid::v7())
            ->setAuthorUserId(Uuid::fromString($authorUserId))
            ->setSubjectAccommodationId(null === $subjectAccommodationId ? null : Uuid::fromString($subjectAccommodationId))
            ->setSubjectUserId(null === $subjectUserId ? null : Uuid::fromString($subjectUserId))
            ->setRating($rating)
            ->setComment($comment)
            ->setCreatedAt(new \DateTimeImmutable($createdAt));

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }
}
