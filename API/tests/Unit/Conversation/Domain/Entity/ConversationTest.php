<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Domain\Entity;

use App\Conversation\Domain\Entity\Conversation;
use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Entity\Message;
use App\Conversation\Domain\Entity\MessageBody;
use App\Conversation\Domain\Entity\MessageId;
use App\Conversation\Domain\Event\ConversationStarted;
use App\Shared\Domain\Event\MessagePosted;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ConversationTest extends TestCase
{
    private const string CONVERSATION_ID = '01961e2f-dead-7000-beef-000000000001';
    private const string RESERVATION_ID = '01961e2f-dead-7000-beef-000000000002';
    private const string ACCOMMODATION_ID = '01961e2f-dead-7000-beef-000000000003';
    private const string TEAM_ID = '01961e2f-dead-7000-beef-000000000004';
    private const string GUEST_USER_ID = '01961e2f-dead-7000-beef-000000000005';

    public function test_should_start_a_conversation_with_an_opening_system_message(): void
    {
        $conversationId = new ConversationId(Uuid::fromString(self::CONVERSATION_ID));
        $reservationId = Uuid::fromString(self::RESERVATION_ID);
        $accommodationId = Uuid::fromString(self::ACCOMMODATION_ID);
        $teamId = Uuid::fromString(self::TEAM_ID);
        $guestUserId = Uuid::fromString(self::GUEST_USER_ID);
        $openingMessageId = new MessageId(Uuid::fromString('01961e2f-dead-7000-beef-000000000010'));
        $openingMessageBody = new MessageBody('Reservation request sent');
        $createdAt = new \DateTimeImmutable('2026-04-13T10:00:00+00:00');

        $conversation = Conversation::start(
            $conversationId,
            $reservationId,
            $accommodationId,
            $teamId,
            $guestUserId,
            $openingMessageId,
            $openingMessageBody,
            $createdAt,
        );

        self::assertSame($conversationId, $conversation->getId());
        self::assertSame($reservationId, $conversation->getReservationId());
        self::assertSame($accommodationId, $conversation->getAccommodationId());
        self::assertSame($teamId, $conversation->getTeamId());
        self::assertSame($guestUserId, $conversation->getGuestUserId());
        self::assertSame($createdAt, $conversation->getCreatedAt());

        $messages = $conversation->getMessages();
        self::assertCount(1, $messages);
        $openingMessage = $messages[0];
        self::assertTrue($openingMessage->isSystem());
        self::assertNull($openingMessage->getAuthorUserId());
        self::assertSame($openingMessageBody, $openingMessage->getBody());
        self::assertSame($createdAt, $openingMessage->getSentAt());
    }

    public function test_should_record_conversation_started_event_on_start(): void
    {
        $conversation = $this->startConversation();

        $events = $conversation->releaseEvents();
        self::assertCount(1, $events);
        $event = $events[0];
        self::assertInstanceOf(ConversationStarted::class, $event);
        self::assertTrue(Uuid::fromString(self::CONVERSATION_ID)->equals($event->conversationId));
        self::assertTrue(Uuid::fromString(self::RESERVATION_ID)->equals($event->reservationId));
        self::assertTrue(Uuid::fromString('01961e2f-dead-7000-beef-000000000010')->equals($event->openingMessageId));
    }

    public function test_should_post_a_system_message(): void
    {
        $conversation = $this->startConversation();
        $conversation->releaseEvents();

        $messageId = new MessageId(Uuid::fromString('01961e2f-dead-7000-beef-000000000011'));
        $body = new MessageBody('Host accepted the reservation');
        $sentAt = new \DateTimeImmutable('2026-04-13T11:00:00+00:00');

        $message = $conversation->postSystemMessage($messageId, $body, $sentAt);

        self::assertInstanceOf(Message::class, $message);
        self::assertTrue($message->isSystem());
        self::assertNull($message->getAuthorUserId());
        self::assertSame($body, $message->getBody());
        self::assertSame($sentAt, $message->getSentAt());
        self::assertCount(2, $conversation->getMessages());
        self::assertSame($message, $conversation->getMessages()[1]);

        $events = $conversation->releaseEvents();
        self::assertCount(1, $events);
        $event = $events[0];
        self::assertInstanceOf(MessagePosted::class, $event);
        self::assertTrue(Uuid::fromString(self::CONVERSATION_ID)->equals($event->conversationId));
        self::assertTrue($messageId->toUuid()->equals($event->messageId));
        self::assertTrue($event->isSystem);
    }

    public function test_should_post_a_guest_message(): void
    {
        $conversation = $this->startConversation();
        $conversation->releaseEvents();

        $messageId = new MessageId(Uuid::fromString('01961e2f-dead-7000-beef-000000000012'));
        $body = new MessageBody('Thanks, looking forward to it!');
        $sentAt = new \DateTimeImmutable('2026-04-13T12:00:00+00:00');

        $message = $conversation->postGuestMessage($messageId, $body, $sentAt);

        self::assertFalse($message->isSystem());
        self::assertNotNull($message->getAuthorUserId());
        self::assertTrue(Uuid::fromString(self::GUEST_USER_ID)->equals($message->getAuthorUserId()));
        self::assertSame($body, $message->getBody());
        self::assertSame($sentAt, $message->getSentAt());
        self::assertCount(2, $conversation->getMessages());

        $events = $conversation->releaseEvents();
        self::assertCount(1, $events);
        $event = $events[0];
        self::assertInstanceOf(MessagePosted::class, $event);
        self::assertTrue(Uuid::fromString(self::CONVERSATION_ID)->equals($event->conversationId));
        self::assertTrue($messageId->toUuid()->equals($event->messageId));
        self::assertFalse($event->isSystem);
    }

    public function test_should_post_a_host_message(): void
    {
        $conversation = $this->startConversation();
        $conversation->releaseEvents();

        $messageId = new MessageId(Uuid::fromString('01961e2f-dead-7000-beef-000000000013'));
        $body = new MessageBody('Welcome! The keys will be in the lockbox.');
        $authorUserId = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');
        $sentAt = new \DateTimeImmutable('2026-04-13T13:00:00+00:00');

        $message = $conversation->postHostMessage($messageId, $body, $authorUserId, $sentAt);

        self::assertFalse($message->isSystem());
        self::assertSame($authorUserId, $message->getAuthorUserId());
        self::assertSame($body, $message->getBody());
        self::assertSame($sentAt, $message->getSentAt());
        self::assertCount(2, $conversation->getMessages());

        $events = $conversation->releaseEvents();
        self::assertCount(1, $events);
        $event = $events[0];
        self::assertInstanceOf(MessagePosted::class, $event);
        self::assertTrue(Uuid::fromString(self::CONVERSATION_ID)->equals($event->conversationId));
        self::assertTrue($messageId->toUuid()->equals($event->messageId));
        self::assertFalse($event->isSystem);
    }

    public function test_should_recognize_the_guest_user(): void
    {
        $conversation = $this->startConversation();

        self::assertTrue($conversation->isGuest(Uuid::fromString(self::GUEST_USER_ID)));
        self::assertFalse($conversation->isGuest(Uuid::fromString('01961e2f-dead-7000-beef-000000000099')));
    }

    public function test_should_construct_with_provided_messages(): void
    {
        $messageId = new MessageId(Uuid::fromString('01961e2f-dead-7000-beef-000000000014'));
        $existing = Message::system($messageId, new MessageBody('Existing'), new \DateTimeImmutable('2026-04-13T09:00:00+00:00'));

        $conversation = new Conversation(
            new ConversationId(Uuid::fromString(self::CONVERSATION_ID)),
            Uuid::fromString(self::RESERVATION_ID),
            Uuid::fromString(self::ACCOMMODATION_ID),
            Uuid::fromString(self::TEAM_ID),
            Uuid::fromString(self::GUEST_USER_ID),
            new \DateTimeImmutable('2026-04-13T08:00:00+00:00'),
            [$existing],
        );

        self::assertCount(1, $conversation->getMessages());
        self::assertSame($existing, $conversation->getMessages()[0]);
        self::assertSame([], $conversation->releaseEvents());
    }

    private function startConversation(): Conversation
    {
        return Conversation::start(
            new ConversationId(Uuid::fromString(self::CONVERSATION_ID)),
            Uuid::fromString(self::RESERVATION_ID),
            Uuid::fromString(self::ACCOMMODATION_ID),
            Uuid::fromString(self::TEAM_ID),
            Uuid::fromString(self::GUEST_USER_ID),
            new MessageId(Uuid::fromString('01961e2f-dead-7000-beef-000000000010')),
            new MessageBody('Reservation request sent'),
            new \DateTimeImmutable('2026-04-13T10:00:00+00:00'),
        );
    }
}
