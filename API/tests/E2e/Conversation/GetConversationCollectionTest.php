<?php

declare(strict_types=1);

namespace App\Tests\E2e\Conversation;

use Symfony\Component\Uid\Uuid;

final class GetConversationCollectionTest extends ConversationApiTestCase
{
    public function test_should_list_conversations_for_authenticated_host(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $this->createAuthUser(email: 'host@example.com', teamId: self::DEFAULT_TEAM_UUID);
        $this->seedConversation(guestUserId: $guestUserId, teamId: self::DEFAULT_TEAM_UUID);

        $response = self::createClient()->request('GET', '/api/conversations', [
            'headers' => $this->authHeaders('host@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame($guestUserId, $members[0]['guestUserId']);
    }

    public function test_should_list_conversations_for_authenticated_guest(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $this->seedConversation(guestUserId: $guestUserId, teamId: self::DEFAULT_TEAM_UUID);

        $response = self::createClient()->request('GET', '/api/conversations', [
            'headers' => $this->authHeaders('guest@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame($guestUserId, $members[0]['guestUserId']);
    }

    public function test_should_require_authentication(): void
    {
        self::createClient()->request('GET', '/api/conversations');

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_ignore_client_supplied_identity_filters(): void
    {
        // A user passing someone else's userId/teamId must still only see their own
        // conversations: the identity is taken from the token, not the query string.
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $otherGuestId = $this->createAuthUser(email: 'other@example.com', teamId: Uuid::v7()->toRfc4122());
        $this->createAuthUser(email: 'outsider@example.com', teamId: Uuid::v7()->toRfc4122());

        $this->seedConversation(guestUserId: $guestUserId, teamId: self::DEFAULT_TEAM_UUID);
        $this->seedConversation(guestUserId: $otherGuestId, teamId: self::DEFAULT_TEAM_UUID);

        $response = self::createClient()->request('GET', '/api/conversations?userId='.$guestUserId.'&teamId='.self::DEFAULT_TEAM_UUID, [
            'headers' => $this->authHeaders('outsider@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame([], $response->toArray()['member']);
    }
}
