<?php

declare(strict_types=1);

namespace App\Tests\E2e\Conversation;

use Symfony\Component\Uid\Uuid;

final class SendMessageTest extends ConversationApiTestCase
{
    public function test_guest_can_post_message(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $conversationId = $this->seedConversation(guestUserId: $guestUserId);

        self::createClient()->request('POST', '/api/conversations/'.$conversationId.'/messages', [
            'headers' => $this->authHeaders('guest@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
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
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $hostUserId = $this->createAuthUser(email: 'host@example.com', teamId: self::DEFAULT_TEAM_UUID);
        $conversationId = $this->seedConversation(guestUserId: $guestUserId, teamId: self::DEFAULT_TEAM_UUID);

        self::createClient()->request('POST', '/api/conversations/'.$conversationId.'/messages', [
            'headers' => $this->authHeaders('host@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'body' => 'Bienvenue à Rodrigues !',
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains([
            'authorUserId' => $hostUserId,
            'isSystem' => false,
        ]);
    }

    public function test_requires_authentication(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $conversationId = $this->seedConversation(guestUserId: $guestUserId);

        self::createClient()->request('POST', '/api/conversations/'.$conversationId.'/messages', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'body' => 'Hello',
            ],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_outsider_cannot_post_message(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $this->createAuthUser(email: 'outsider@example.com', teamId: Uuid::v7()->toRfc4122());
        $conversationId = $this->seedConversation(guestUserId: $guestUserId, teamId: self::DEFAULT_TEAM_UUID);

        self::createClient()->request('POST', '/api/conversations/'.$conversationId.'/messages', [
            'headers' => $this->authHeaders('outsider@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'body' => 'Hi',
            ],
        ]);

        // The author is neither the guest nor a host team member: the use case rejects
        // them with a ConversationParticipantException (\DomainException → 422).
        self::assertResponseStatusCodeSame(422);
    }

    public function test_rejects_message_when_conversation_does_not_exist(): void
    {
        $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $unknownConversationId = Uuid::v7()->toRfc4122();

        self::createClient()->request('POST', '/api/conversations/'.$unknownConversationId.'/messages', [
            'headers' => $this->authHeaders('guest@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'body' => 'Hello',
            ],
        ]);

        // ConversationNotFoundException is a plain \DomainException with no explicit
        // ApiPlatform status mapping, so it surfaces as 422 (not 404 as the OpenAPI doc states).
        self::assertResponseStatusCodeSame(422);
    }

    public function test_empty_body_is_rejected(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $conversationId = $this->seedConversation(guestUserId: $guestUserId);

        self::createClient()->request('POST', '/api/conversations/'.$conversationId.'/messages', [
            'headers' => $this->authHeaders('guest@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'body' => '   ',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
