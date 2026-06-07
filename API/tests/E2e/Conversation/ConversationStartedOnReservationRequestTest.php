<?php

declare(strict_types=1);

namespace App\Tests\E2e\Conversation;

use Symfony\Component\Uid\Uuid;

final class ConversationStartedOnReservationRequestTest extends ConversationApiTestCase
{
    public function test_should_create_conversation_and_opening_message_when_reservation_is_requested(): void
    {
        $teamId = Uuid::fromString(self::DEFAULT_TEAM_UUID);
        $accommodationId = $this->insertAccommodation($teamId);
        $guestUserId = $this->insertUser(teamId: Uuid::v7());

        $reservationId = $this->requestReservationViaApi(
            accommodationId: $accommodationId,
            guestUserId: $guestUserId,
            note: 'Nous avons un chien.',
        );

        $response = self::createClient()->request('GET', '/api/conversations?userId='.$guestUserId);

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);

        $conversation = $members[0];
        self::assertSame($reservationId, $conversation['reservationId']);
        self::assertSame($guestUserId, $conversation['guestUserId']);
        self::assertCount(1, $conversation['messages']);
        $message = $conversation['messages'][0];
        self::assertTrue($message['isSystem']);
        self::assertNull($message['authorUserId']);
        self::assertStringContainsString('Nous avons un chien.', $message['body']);
        self::assertStringContainsString('Jean Dupont', $message['body']);
    }

    public function test_should_expose_conversation_detail_by_id(): void
    {
        $accommodationId = $this->insertAccommodation();
        $guestUserId = $this->insertUser(teamId: Uuid::v7());

        $reservationId = $this->requestReservationViaApi($accommodationId, $guestUserId);

        $list = self::createClient()->request('GET', '/api/conversations?userId='.$guestUserId);
        $conversationId = $list->toArray()['member'][0]['id'];

        $detail = self::createClient()->request('GET', '/api/conversations/'.$conversationId);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $conversationId,
            'reservationId' => $reservationId,
            'guestUserId' => $guestUserId,
        ]);
    }
}
