<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Application\Listener;

use App\Notification\Application\Email\MessageEmails;
use App\Notification\Application\Listener\SendMessageEmailOnMessagePosted;
use App\Notification\Application\UseCase\QueueEmail;
use App\Shared\Domain\Event\MessagePosted;
use App\Shared\Domain\Port\AccommodationSummary;
use App\Shared\Domain\Port\ConversationMessageView;
use App\Shared\Domain\Port\UserContact;
use App\Tests\Unit\Notification\Infrastructure\FakeEmailRenderer;
use App\Tests\Unit\Notification\Infrastructure\FixedClock;
use App\Tests\Unit\Notification\Infrastructure\InMemoryAccommodationSummaryProvider;
use App\Tests\Unit\Notification\Infrastructure\InMemoryConversationMessageProvider;
use App\Tests\Unit\Notification\Infrastructure\InMemoryEmailOutbox;
use App\Tests\Unit\Notification\Infrastructure\InMemoryTeamContactProvider;
use App\Tests\Unit\Notification\Infrastructure\InMemoryUserContactProvider;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SendMessageEmailOnMessagePostedTest extends TestCase
{
    private InMemoryEmailOutbox $outbox;
    private InMemoryConversationMessageProvider $messages;
    private InMemoryTeamContactProvider $teamContacts;
    private InMemoryUserContactProvider $contacts;
    private InMemoryAccommodationSummaryProvider $accommodations;
    private SendMessageEmailOnMessagePosted $listener;

    private Uuid $conversationId;
    private Uuid $messageId;
    private Uuid $teamId;
    private Uuid $accommodationId;
    private Uuid $guestUserId;
    private Uuid $hostUserId;

    #[Before]
    public function initListener(): void
    {
        $this->outbox = new InMemoryEmailOutbox();
        $this->messages = new InMemoryConversationMessageProvider();
        $this->teamContacts = new InMemoryTeamContactProvider();
        $this->contacts = new InMemoryUserContactProvider();
        $this->accommodations = new InMemoryAccommodationSummaryProvider();

        $this->listener = new SendMessageEmailOnMessagePosted(
            $this->messages,
            $this->teamContacts,
            $this->contacts,
            $this->accommodations,
            new MessageEmails(),
            new QueueEmail($this->outbox, new FakeEmailRenderer(), new FixedClock(new \DateTimeImmutable('2026-06-16 09:00:00'))),
        );

        $this->conversationId = Uuid::fromString('01961e2f-beef-7000-dead-000000000001');
        $this->messageId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000f1');
        $this->teamId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000d1');
        $this->accommodationId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000b1');
        $this->guestUserId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000c1');
        $this->hostUserId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000a1');

        $this->accommodations->add(new AccommodationSummary($this->accommodationId, 'Villa Corail', 'Port Mathurin'));
        $this->contacts->add(new UserContact($this->guestUserId, 'marie@example.com', 'Marie'));
        $this->contacts->add(new UserContact($this->hostUserId, 'jean@example.com', 'Jean'));
        $this->teamContacts->addContact($this->teamId, new UserContact($this->hostUserId, 'jean@example.com', 'Jean'));
    }

    public function test_should_notify_the_host_when_the_guest_writes(): void
    {
        $this->messages->add($this->message(authorUserId: $this->guestUserId, body: 'Bonjour, une question sur le parking ?'));

        ($this->listener)(new MessagePosted($this->conversationId, $this->messageId, false));

        $queued = $this->outbox->all();
        self::assertCount(1, $queued);
        self::assertSame('jean@example.com', $queued[0]->getRecipient()->toString());
        self::assertStringContainsString('Marie', $queued[0]->getSubject());
        self::assertStringContainsString('parking', $queued[0]->getHtmlBody());
    }

    public function test_should_notify_the_guest_when_the_host_writes(): void
    {
        $this->messages->add($this->message(authorUserId: $this->hostUserId, body: 'Bonjour Marie, avec plaisir !'));

        ($this->listener)(new MessagePosted($this->conversationId, $this->messageId, false));

        $queued = $this->outbox->all();
        self::assertCount(1, $queued);
        self::assertSame('marie@example.com', $queued[0]->getRecipient()->toString());
        self::assertStringContainsString('Jean', $queued[0]->getSubject());
    }

    public function test_should_ignore_system_messages(): void
    {
        $this->messages->add($this->message(authorUserId: null, body: 'Réservation confirmée', isSystem: true));

        ($this->listener)(new MessagePosted($this->conversationId, $this->messageId, true));

        self::assertCount(0, $this->outbox->all());
    }

    public function test_should_notify_every_co_host_when_the_guest_writes(): void
    {
        $coHostId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000a2');
        $this->teamContacts->addContact($this->teamId, new UserContact($coHostId, 'paul@example.com', 'Paul'));
        $this->messages->add($this->message(authorUserId: $this->guestUserId, body: 'Bonjour !'));

        ($this->listener)(new MessagePosted($this->conversationId, $this->messageId, false));

        $recipients = array_map(static fn ($e) => $e->getRecipient()->toString(), $this->outbox->all());
        self::assertCount(2, $recipients);
        self::assertContains('jean@example.com', $recipients);
        self::assertContains('paul@example.com', $recipients);
    }

    private function message(?Uuid $authorUserId, string $body, bool $isSystem = false): ConversationMessageView
    {
        return new ConversationMessageView(
            conversationId: $this->conversationId,
            messageId: $this->messageId,
            teamId: $this->teamId,
            guestUserId: $this->guestUserId,
            accommodationId: $this->accommodationId,
            authorUserId: $authorUserId,
            body: $body,
            isSystem: $isSystem,
        );
    }
}
