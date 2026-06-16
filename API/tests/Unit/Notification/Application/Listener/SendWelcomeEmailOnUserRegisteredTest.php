<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Application\Listener;

use App\Notification\Application\Email\TravelerEmails;
use App\Notification\Application\Listener\SendWelcomeEmailOnUserRegistered;
use App\Notification\Application\UseCase\QueueEmail;
use App\Shared\Domain\Event\UserRegistered;
use App\Shared\Domain\Port\UserContact;
use App\Tests\Unit\Notification\Infrastructure\FixedClock;
use App\Tests\Unit\Notification\Infrastructure\InMemoryEmailOutbox;
use App\Tests\Unit\Notification\Infrastructure\InMemoryUserContactProvider;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SendWelcomeEmailOnUserRegisteredTest extends TestCase
{
    private InMemoryEmailOutbox $outbox;
    private InMemoryUserContactProvider $contacts;
    private SendWelcomeEmailOnUserRegistered $listener;

    #[Before]
    public function initListener(): void
    {
        $this->outbox = new InMemoryEmailOutbox();
        $this->contacts = new InMemoryUserContactProvider();
        $this->listener = new SendWelcomeEmailOnUserRegistered(
            $this->contacts,
            new TravelerEmails(),
            new QueueEmail($this->outbox, new FixedClock(new \DateTimeImmutable('2026-06-16 09:00:00'))),
        );
    }

    public function test_should_queue_a_welcome_email_for_the_new_user(): void
    {
        $userId = Uuid::fromString('01961e2f-beef-7000-dead-000000000001');
        $teamId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000a1');
        $this->contacts->add(new UserContact($userId, 'marie@example.com', 'Marie'));

        ($this->listener)(new UserRegistered($userId, $teamId));

        $queued = $this->outbox->all();
        self::assertCount(1, $queued);
        self::assertSame('marie@example.com', $queued[0]->getRecipient()->toString());
        self::assertStringContainsString('Bienvenue', $queued[0]->getSubject());
        self::assertStringContainsString('Bonjour Marie', $queued[0]->getHtmlBody());
    }

    public function test_should_do_nothing_when_the_user_contact_is_unknown(): void
    {
        $userId = Uuid::fromString('01961e2f-beef-7000-dead-000000000009');
        $teamId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000a9');

        ($this->listener)(new UserRegistered($userId, $teamId));

        self::assertCount(0, $this->outbox->all());
    }
}
