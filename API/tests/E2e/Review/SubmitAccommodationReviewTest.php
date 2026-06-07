<?php

declare(strict_types=1);

namespace App\Tests\E2e\Review;

use App\Tests\E2e\AuthenticatedClientTrait;
use Symfony\Component\Uid\Uuid;

final class SubmitAccommodationReviewTest extends ReviewApiTestCase
{
    use AuthenticatedClientTrait;

    private const string VALID_COMMENT = 'Séjour vraiment agréable, logement propre, bien situé et hôte très réactif. Je recommande sans hésiter.';

    public function test_should_submit_accommodation_review(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $guestUserId = $this->createAuthUser(email: 'traveller@example.com');
        $this->insertCompletedStay($accommodationId, $guestUserId);

        self::createClient()->request('POST', '/api/reviews/accommodation', [
            'headers' => $this->authHeaders('traveller@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
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
        $guestUserId = $this->createAuthUser(email: 'traveller@example.com');
        $this->insertCompletedStay($accommodationId, $guestUserId);

        self::createClient()->request('POST', '/api/reviews/accommodation', [
            'headers' => $this->authHeaders('traveller@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
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
        $guestUserId = $this->createAuthUser(email: 'traveller@example.com');
        $this->insertCompletedStay($accommodationId, $guestUserId);

        self::createClient()->request('POST', '/api/reviews/accommodation', [
            'headers' => $this->authHeaders('traveller@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => $accommodationId,
                'rating' => 7,
                'comment' => self::VALID_COMMENT,
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return_422_when_stay_not_completed(): void
    {
        // No reservation inserted: there is no completed stay for this authenticated traveller.
        $this->createAuthUser(email: 'traveller@example.com');

        self::createClient()->request('POST', '/api/reviews/accommodation', [
            'headers' => $this->authHeaders('traveller@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
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
        $guestUserId = $this->createAuthUser(email: 'traveller@example.com');
        $this->insertCompletedStay($accommodationId, $guestUserId);

        $payload = [
            'headers' => $this->authHeaders('traveller@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
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
        $guestUserId = $this->createAuthUser(email: 'traveller@example.com');
        $this->insertCompletedStay($accommodationId, $guestUserId);

        self::createClient()->request('POST', '/api/reviews/accommodation', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => $accommodationId,
                'rating' => 5,
                'comment' => self::VALID_COMMENT,
            ],
        ]);

        self::assertResponseStatusCodeSame(401);
    }
}
