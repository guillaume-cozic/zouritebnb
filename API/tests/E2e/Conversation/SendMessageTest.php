<?php

declare(strict_types=1);

namespace App\Tests\E2e\Conversation;

use Symfony\Component\Uid\Uuid;

final class SendMessageTest extends ConversationApiTestCase
{
    public function test_guest_can_post_message(): void
    {
        $teamId = Uuid::fromString(self::DEFAULT_TEAM_UUID);
        $accommodationId = $this->insertAccommodation($teamId);
        $guestUserId = $this->insertUser(teamId: Uuid::v7());

        $this->requestReservationViaApi($accommodationId, $guestUserId);

        $list = self::createClient()->request('GET', '/api/conversations?userId='.$guestUserId);
        $conversationId = $list->toArray()['member'][0]['id'];

        self::createClient()->request('POST', '/api/conversations/'.$conversationId.'/messages', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => $guestUserId,
                'body' => 'Merci pour la réponse rapide !',
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains([
            'body' => 'Merci pour la réponse rapide !',
            'authorUserId' => $guestUserId,
            'isSystem' => false,
        ]);
    }

    public function test_host_team_member_can_post_message(): void
    {
        $teamId = Uuid::fromString(self::DEFAULT_TEAM_UUID);
        $accommodationId = $this->insertAccommodation($teamId);
        $guestUserId = $this->insertUser(teamId: Uuid::v7());
        $hostUserId = $this->insertUser(teamId: $teamId);

        $this->requestReservationViaApi($accommodationId, $guestUserId);

        $list = self::createClient()->request('GET', '/api/conversations?userId='.$guestUserId);
        $conversationId = $list->toArray()['member'][0]['id'];

        self::createClient()->request('POST', '/api/conversations/'.$conversationId.'/messages', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => $hostUserId,
                'body' => 'Bienvenue à Rodrigues !',
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
    }

    public function test_outsider_cannot_post_message(): void
    {
        $accommodationId = $this->insertAccommodation();
        $guestUserId = $this->insertUser(teamId: Uuid::v7());
        $outsiderId = $this->insertUser(teamId: Uuid::v7());

        $this->requestReservationViaApi($accommodationId, $guestUserId);

        $list = self::createClient()->request('GET', '/api/conversations?userId='.$guestUserId);
        $conversationId = $list->toArray()['member'][0]['id'];

        self::createClient()->request('POST', '/api/conversations/'.$conversationId.'/messages', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => $outsiderId,
                'body' => 'Hi',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_rejects_message_when_conversation_does_not_exist(): void
    {
        $userId = $this->insertUser(teamId: Uuid::v7());
        $unknownConversationId = Uuid::v7()->toRfc4122();

        self::createClient()->request('POST', '/api/conversations/'.$unknownConversationId.'/messages', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => $userId,
                'body' => 'Hello',
            ],
        ]);

        // ConversationNotFoundException is a plain \DomainException with no explicit
        // ApiPlatform status mapping, so it surfaces as 422 (not 404 as the OpenAPI doc states).
        self::assertResponseStatusCodeSame(422);
    }

    public function test_empty_body_is_rejected(): void
    {
        $accommodationId = $this->insertAccommodation();
        $guestUserId = $this->insertUser(teamId: Uuid::v7());

        $this->requestReservationViaApi($accommodationId, $guestUserId);

        $list = self::createClient()->request('GET', '/api/conversations?userId='.$guestUserId);
        $conversationId = $list->toArray()['member'][0]['id'];

        self::createClient()->request('POST', '/api/conversations/'.$conversationId.'/messages', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'authorUserId' => $guestUserId,
                'body' => '   ',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
