<?php

declare(strict_types=1);

namespace App\Tests\E2e\Review;

use Symfony\Component\Uid\Uuid;

final class SubmitAccommodationReviewTest extends ReviewApiTestCase
{
    private const string VALID_COMMENT = 'Séjour vraiment agréable, logement propre, bien situé et hôte très réactif. Je recommande sans hésiter.';

    public function test_should_submit_accommodation_review(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $guestUserId = Uuid::v7()->toRfc4122();
        $this->insertCompletedStay($accommodationId, $guestUserId);

        self::createClient()->request('POST', '/api/reviews/accommodation', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => $guestUserId,
                'accommodationId' => $accommodationId,
                'rating' => 5,
                'comment' => self::VALID_COMMENT,
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
    }

    public function test_should_return_422_when_comment_too_short(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $guestUserId = Uuid::v7()->toRfc4122();
        $this->insertCompletedStay($accommodationId, $guestUserId);

        self::createClient()->request('POST', '/api/reviews/accommodation', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => $guestUserId,
                'accommodationId' => $accommodationId,
                'rating' => 5,
                'comment' => 'Très bien.',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return_422_when_rating_out_of_bounds(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $guestUserId = Uuid::v7()->toRfc4122();
        $this->insertCompletedStay($accommodationId, $guestUserId);

        self::createClient()->request('POST', '/api/reviews/accommodation', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => $guestUserId,
                'accommodationId' => $accommodationId,
                'rating' => 7,
                'comment' => self::VALID_COMMENT,
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return_422_when_stay_not_completed(): void
    {
        // No reservation inserted: there is no completed stay for this guest/accommodation.
        self::createClient()->request('POST', '/api/reviews/accommodation', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => Uuid::v7()->toRfc4122(),
                'accommodationId' => Uuid::v7()->toRfc4122(),
                'rating' => 4,
                'comment' => self::VALID_COMMENT,
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return_422_when_review_already_submitted(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $guestUserId = Uuid::v7()->toRfc4122();
        $this->insertCompletedStay($accommodationId, $guestUserId);

        $payload = [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => $guestUserId,
                'accommodationId' => $accommodationId,
                'rating' => 5,
                'comment' => self::VALID_COMMENT,
            ],
        ];

        self::createClient()->request('POST', '/api/reviews/accommodation', $payload);
        self::assertResponseStatusCodeSame(201);

        self::createClient()->request('POST', '/api/reviews/accommodation', $payload);
        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $guestUserId = Uuid::v7()->toRfc4122();
        $this->insertCompletedStay($accommodationId, $guestUserId);

        self::createClient()->request('POST', '/api/reviews/accommodation', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => '',
                'accommodationId' => $accommodationId,
                'rating' => 5,
                'comment' => self::VALID_COMMENT,
            ],
        ]);

        self::assertResponseStatusCodeSame(401);
    }
}
