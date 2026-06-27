<?php

declare(strict_types=1);

namespace App\Tests\E2e\Review;

use App\Review\Infrastructure\Doctrine\ReviewEntity;
use App\Tests\E2e\AuthenticatedClientTrait;
use Symfony\Component\Uid\Uuid;

final class ReplyToReviewTest extends ReviewApiTestCase
{
    use AuthenticatedClientTrait;

    private const string COMMENT = 'Un séjour vraiment agréable, logement propre, bien situé et hôte très réactif.';

    public function test_should_reply_to_a_review_as_host_and_expose_it_publicly(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $guestUserId = Uuid::v7()->toRfc4122();
        $this->createAuthUser(email: 'host@example.com', teamId: self::DEFAULT_TEAM_UUID);
        $reservationId = $this->insertCompletedStay($accommodationId, $guestUserId, self::DEFAULT_TEAM_UUID);
        $reviewId = $this->insertAccommodationReview($reservationId, $accommodationId, $guestUserId);

        self::createClient()->request('PATCH', '/api/reviews/'.$reviewId.'/reply', [
            'headers' => $this->authHeaders('host@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['reply' => 'Merci pour votre séjour, au plaisir de vous revoir !'],
        ]);

        self::assertResponseStatusCodeSame(204);

        $response = self::createClient()->request('GET', '/api/accommodations/'.$accommodationId.'/reviews');
        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('Merci pour votre séjour, au plaisir de vous revoir !', $members[0]['hostReply']);
        self::assertNotNull($members[0]['hostReplyAt']);
    }

    public function test_should_return_403_when_not_in_host_team(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $guestUserId = Uuid::v7()->toRfc4122();
        $this->createAuthUser(email: 'outsider@example.com', teamId: Uuid::v7()->toRfc4122());
        $reservationId = $this->insertCompletedStay($accommodationId, $guestUserId, self::DEFAULT_TEAM_UUID);
        $reviewId = $this->insertAccommodationReview($reservationId, $accommodationId, $guestUserId);

        self::createClient()->request('PATCH', '/api/reviews/'.$reviewId.'/reply', [
            'headers' => $this->authHeaders('outsider@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['reply' => 'Merci !'],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return_404_when_review_unknown(): void
    {
        $this->createAuthUser(email: 'host@example.com', teamId: self::DEFAULT_TEAM_UUID);

        self::createClient()->request('PATCH', '/api/reviews/'.Uuid::v7()->toRfc4122().'/reply', [
            'headers' => $this->authHeaders('host@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['reply' => 'Merci !'],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_return_422_when_reply_is_blank(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $guestUserId = Uuid::v7()->toRfc4122();
        $this->createAuthUser(email: 'host@example.com', teamId: self::DEFAULT_TEAM_UUID);
        $reservationId = $this->insertCompletedStay($accommodationId, $guestUserId, self::DEFAULT_TEAM_UUID);
        $reviewId = $this->insertAccommodationReview($reservationId, $accommodationId, $guestUserId);

        self::createClient()->request('PATCH', '/api/reviews/'.$reviewId.'/reply', [
            'headers' => $this->authHeaders('host@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['reply' => '   '],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $guestUserId = Uuid::v7()->toRfc4122();
        $reservationId = $this->insertCompletedStay($accommodationId, $guestUserId, self::DEFAULT_TEAM_UUID);
        $reviewId = $this->insertAccommodationReview($reservationId, $accommodationId, $guestUserId);

        self::createClient()->request('PATCH', '/api/reviews/'.$reviewId.'/reply', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['reply' => 'Merci !'],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    private function insertAccommodationReview(string $reservationId, string $accommodationId, string $authorUserId): string
    {
        $em = $this->entityManager();

        $id = Uuid::v7();
        $entity = new ReviewEntity()
            ->setId($id)
            ->setType('accommodation')
            ->setReservationId(Uuid::fromString($reservationId))
            ->setAuthorUserId(Uuid::fromString($authorUserId))
            ->setSubjectAccommodationId(Uuid::fromString($accommodationId))
            ->setRating(5)
            ->setComment(self::COMMENT)
            ->setCreatedAt(new \DateTimeImmutable('2026-05-12T14:30:00+00:00'));

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }
}
