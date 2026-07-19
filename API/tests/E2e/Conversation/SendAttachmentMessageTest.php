<?php

declare(strict_types=1);

namespace App\Tests\E2e\Conversation;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

final class SendAttachmentMessageTest extends ConversationApiTestCase
{
    public function test_guest_can_post_photo(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $conversationId = $this->seedConversation(guestUserId: $guestUserId);

        $response = self::createClient()->request('POST', '/api/conversations/'.$conversationId.'/attachments', [
            'headers' => $this->authHeaders('guest@example.com') + ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => ['file' => $this->createTempImage()],
            ],
        ]);

        self::assertResponseStatusCodeSame(201);

        $data = $response->toArray();
        self::assertNull($data['body'] ?? null);
        self::assertSame($guestUserId, $data['authorUserId']);
        self::assertFalse($data['isSystem']);
        self::assertMatchesRegularExpression('#^/uploads/photos/[0-9a-f-]+\.webp$#', $data['attachmentUrl']);
    }

    public function test_guest_can_post_photo_with_caption(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $conversationId = $this->seedConversation(guestUserId: $guestUserId);

        self::createClient()->request('POST', '/api/conversations/'.$conversationId.'/attachments', [
            'headers' => $this->authHeaders('guest@example.com') + ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => ['body' => 'Voici une photo du problème.'],
                'files' => ['file' => $this->createTempImage()],
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains([
            'body' => 'Voici une photo du problème.',
            'authorUserId' => $guestUserId,
        ]);
    }

    public function test_host_team_member_can_post_photo(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $hostUserId = $this->createAuthUser(email: 'host@example.com', teamId: self::DEFAULT_TEAM_UUID);
        $conversationId = $this->seedConversation(guestUserId: $guestUserId, teamId: self::DEFAULT_TEAM_UUID);

        self::createClient()->request('POST', '/api/conversations/'.$conversationId.'/attachments', [
            'headers' => $this->authHeaders('host@example.com') + ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => ['file' => $this->createTempImage()],
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

        self::createClient()->request('POST', '/api/conversations/'.$conversationId.'/attachments', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => ['file' => $this->createTempImage()],
            ],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_outsider_cannot_post_photo(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $this->createAuthUser(email: 'outsider@example.com', teamId: Uuid::v7()->toRfc4122());
        $conversationId = $this->seedConversation(guestUserId: $guestUserId, teamId: self::DEFAULT_TEAM_UUID);

        self::createClient()->request('POST', '/api/conversations/'.$conversationId.'/attachments', [
            'headers' => $this->authHeaders('outsider@example.com') + ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => ['file' => $this->createTempImage()],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_rejects_non_image_file(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $conversationId = $this->seedConversation(guestUserId: $guestUserId);

        $tmpFile = tempnam(sys_get_temp_dir(), 'attachment_test_');
        file_put_contents($tmpFile, str_repeat('x', 100));
        $uploadedFile = new UploadedFile($tmpFile, 'notes.txt', 'text/plain', test: true);

        self::createClient()->request('POST', '/api/conversations/'.$conversationId.'/attachments', [
            'headers' => $this->authHeaders('guest@example.com') + ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => ['file' => $uploadedFile],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    private function createTempImage(): UploadedFile
    {
        $image = imagecreatetruecolor(1, 1);
        $tmpFile = tempnam(sys_get_temp_dir(), 'attachment_test_');
        imagejpeg($image, $tmpFile);

        return new UploadedFile($tmpFile, 'photo.jpg', 'image/jpeg', test: true);
    }
}
