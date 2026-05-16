<?php

declare(strict_types=1);

namespace App\Tests\E2e\Conversation;

use Symfony\Component\Uid\Uuid;

final class SendMessageTest extends ConversationApiTestCase
{
    public function testGuestCanPostMessage(): void
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

    public function testHostTeamMemberCanPostMessage(): void
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

    public function testOutsiderCannotPostMessage(): void
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

    public function testEmptyBodyIsRejected(): void
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
