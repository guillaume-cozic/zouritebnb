<?php

declare(strict_types=1);

namespace App\Tests\E2e\Review;

use Symfony\Component\Uid\Uuid;

final class SubmitGuestReviewTest extends ReviewApiTestCase
{
    private const string VALID_COMMENT = 'Voyageur exemplaire : communication parfaite, logement laissé impeccable et respect total du règlement intérieur.';

    public function test_should_submit_guest_review(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $guestUserId = Uuid::v7()->toRfc4122();
        $hostUserId = $this->insertUser(self::DEFAULT_TEAM_UUID);
        $this->insertCompletedStay($accommodationId, $guestUserId, self::DEFAULT_TEAM_UUID);

        self::createClient()->request('POST', '/api/reviews/guest', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => $hostUserId,
                'accommodationId' => $accommodationId,
                'guestUserId' => $guestUserId,
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
        $hostUserId = $this->insertUser(self::DEFAULT_TEAM_UUID);
        $this->insertCompletedStay($accommodationId, $guestUserId, self::DEFAULT_TEAM_UUID);

        self::createClient()->request('POST', '/api/reviews/guest', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => $hostUserId,
                'accommodationId' => $accommodationId,
                'guestUserId' => $guestUserId,
                'rating' => 5,
                'comment' => 'Parfait.',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return_422_when_rating_out_of_bounds(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $guestUserId = Uuid::v7()->toRfc4122();
        $hostUserId = $this->insertUser(self::DEFAULT_TEAM_UUID);
        $this->insertCompletedStay($accommodationId, $guestUserId, self::DEFAULT_TEAM_UUID);

        self::createClient()->request('POST', '/api/reviews/guest', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => $hostUserId,
                'accommodationId' => $accommodationId,
                'guestUserId' => $guestUserId,
                'rating' => 0,
                'comment' => self::VALID_COMMENT,
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return_422_when_stay_not_completed(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $guestUserId = Uuid::v7()->toRfc4122();
        $hostUserId = $this->insertUser(self::DEFAULT_TEAM_UUID);
        // Reservation exists (so the team can be resolved) but is still pending: the stay is not completed.
        $this->insertCompletedStay($accommodationId, $guestUserId, self::DEFAULT_TEAM_UUID, status: 'pending');

        self::createClient()->request('POST', '/api/reviews/guest', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => $hostUserId,
                'accommodationId' => $accommodationId,
                'guestUserId' => $guestUserId,
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
        $hostUserId = $this->insertUser(self::DEFAULT_TEAM_UUID);
        $this->insertCompletedStay($accommodationId, $guestUserId, self::DEFAULT_TEAM_UUID);

        $payload = [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => $hostUserId,
                'accommodationId' => $accommodationId,
                'guestUserId' => $guestUserId,
                'rating' => 5,
                'comment' => self::VALID_COMMENT,
            ],
        ];

        self::createClient()->request('POST', '/api/reviews/guest', $payload);
        self::assertResponseStatusCodeSame(201);

        self::createClient()->request('POST', '/api/reviews/guest', $payload);
        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return_403_when_author_not_in_host_team(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $guestUserId = Uuid::v7()->toRfc4122();
        $otherTeamId = Uuid::v7()->toRfc4122();
        $outsiderUserId = $this->insertUser($otherTeamId);
        $this->insertCompletedStay($accommodationId, $guestUserId, self::DEFAULT_TEAM_UUID);

        self::createClient()->request('POST', '/api/reviews/guest', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => $outsiderUserId,
                'accommodationId' => $accommodationId,
                'guestUserId' => $guestUserId,
                'rating' => 5,
                'comment' => self::VALID_COMMENT,
            ],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $guestUserId = Uuid::v7()->toRfc4122();
        $this->insertCompletedStay($accommodationId, $guestUserId, self::DEFAULT_TEAM_UUID);

        self::createClient()->request('POST', '/api/reviews/guest', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => '',
                'accommodationId' => $accommodationId,
                'guestUserId' => $guestUserId,
                'rating' => 5,
                'comment' => self::VALID_COMMENT,
            ],
        ]);

        self::assertResponseStatusCodeSame(401);
    }
}
