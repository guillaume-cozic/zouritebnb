<?php

declare(strict_types=1);

namespace App\Tests\Integration\Notification\Infrastructure;

use App\Notification\Domain\Entity\EmailAddress;
use App\Notification\Domain\Entity\EmailStatus;
use App\Notification\Domain\Entity\OutboxEmail;
use App\Notification\Domain\Port\EmailOutbox;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineEmailOutboxTest extends RepositoryTestCase
{
    private EmailOutbox $outbox;

    #[Before]
    public function initRepository(): void
    {
        $this->outbox = self::getContainer()->get(EmailOutbox::class);
    }

    public function test_should_save_and_find_by_id(): void
    {
        $id = Uuid::v7();
        $createdAt = new \DateTimeImmutable('2026-06-16T09:00:00+00:00');
        $email = OutboxEmail::queue(
            id: $id,
            recipient: new EmailAddress('marie@example.com'),
            recipientName: 'Marie',
            subject: 'Bienvenue sur BnB Rodrigues',
            htmlBody: '<p>Bonjour Marie</p>',
            createdAt: $createdAt,
        );

        $this->outbox->save($email);
        $found = $this->outbox->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertSame('marie@example.com', $found->getRecipient()->toString());
        self::assertSame('Marie', $found->getRecipientName());
        self::assertSame('Bienvenue sur BnB Rodrigues', $found->getSubject());
        self::assertSame('<p>Bonjour Marie</p>', $found->getHtmlBody());
        self::assertSame(EmailStatus::Pending, $found->getStatus());
        self::assertSame(0, $found->getAttempts());
        self::assertEquals($createdAt, $found->getCreatedAt());
    }

    public function test_should_return_null_when_not_found(): void
    {
        self::assertNull($this->outbox->findById(Uuid::v4()));
    }

    public function test_should_only_return_pending_emails_ordered_by_creation(): void
    {
        $older = $this->queue('2026-06-16T08:00:00+00:00');
        $newer = $this->queue('2026-06-16T09:00:00+00:00');
        $sentEmail = $this->queue('2026-06-16T07:00:00+00:00');
        $sentEmail->markSent(new \DateTimeImmutable('2026-06-16T07:05:00+00:00'));
        $this->outbox->save($sentEmail);

        $pending = $this->outbox->findPending(10);

        self::assertCount(2, $pending);
        self::assertEquals($older->getId(), $pending[0]->getId());
        self::assertEquals($newer->getId(), $pending[1]->getId());
    }

    public function test_should_persist_a_failed_attempt(): void
    {
        $email = $this->queue('2026-06-16T09:00:00+00:00');
        $email->recordFailedAttempt('transport down', new \DateTimeImmutable('2026-06-16T09:01:00+00:00'), 5);
        $this->outbox->save($email);

        $found = $this->outbox->findById($email->getId());

        self::assertSame(EmailStatus::Pending, $found?->getStatus());
        self::assertSame(1, $found->getAttempts());
        self::assertSame('transport down', $found->getError());
    }

    private function queue(string $createdAt): OutboxEmail
    {
        $email = OutboxEmail::queue(
            id: Uuid::v7(),
            recipient: new EmailAddress('marie@example.com'),
            recipientName: 'Marie',
            subject: 'Sujet',
            htmlBody: '<p>Corps</p>',
            createdAt: new \DateTimeImmutable($createdAt),
        );
        $this->outbox->save($email);

        return $email;
    }
}
