<?php

declare(strict_types=1);

namespace App\Tests\E2e\Conversation;

use Symfony\Component\Uid\Uuid;

final class GetConversationTest extends ConversationApiTestCase
{
    public function test_guest_can_read_their_conversation(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $conversationId = $this->seedConversation(guestUserId: $guestUserId, teamId: self::DEFAULT_TEAM_UUID);

        self::createClient()->request('GET', '/api/conversations/'.$conversationId, [
            'headers' => $this->authHeaders('guest@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $conversationId,
            'guestUserId' => $guestUserId,
        ]);
    }

    public function test_host_team_member_can_read_the_conversation(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $this->createAuthUser(email: 'host@example.com', teamId: self::DEFAULT_TEAM_UUID);
        $conversationId = $this->seedConversation(guestUserId: $guestUserId, teamId: self::DEFAULT_TEAM_UUID);

        self::createClient()->request('GET', '/api/conversations/'.$conversationId, [
            'headers' => $this->authHeaders('host@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['id' => $conversationId]);
    }

    public function test_requires_authentication(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $conversationId = $this->seedConversation(guestUserId: $guestUserId);

        self::createClient()->request('GET', '/api/conversations/'.$conversationId);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_non_participant_is_forbidden(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $this->createAuthUser(email: 'outsider@example.com', teamId: Uuid::v7()->toRfc4122());
        $conversationId = $this->seedConversation(guestUserId: $guestUserId, teamId: self::DEFAULT_TEAM_UUID);

        self::createClient()->request('GET', '/api/conversations/'.$conversationId, [
            'headers' => $this->authHeaders('outsider@example.com'),
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_returns_404_when_not_found(): void
    {
        $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());

        self::createClient()->request('GET', '/api/conversations/'.Uuid::v7()->toRfc4122(), [
            'headers' => $this->authHeaders('guest@example.com'),
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
