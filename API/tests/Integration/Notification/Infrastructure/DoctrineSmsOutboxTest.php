<?php

declare(strict_types=1);

namespace App\Tests\Integration\Notification\Infrastructure;

use App\Notification\Domain\Entity\OutboxSms;
use App\Notification\Domain\Entity\OutboxStatus;
use App\Notification\Domain\Entity\PhoneNumber;
use App\Notification\Domain\Port\SmsOutbox;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineSmsOutboxTest extends RepositoryTestCase
{
    private SmsOutbox $outbox;

    #[Before]
    public function initRepository(): void
    {
        $this->outbox = self::getContainer()->get(SmsOutbox::class);
    }

    public function test_should_save_and_find_by_id(): void
    {
        $id = Uuid::v7();
        $createdAt = new \DateTimeImmutable('2026-06-17T09:00:00+00:00');
        $sms = OutboxSms::queue($id, new PhoneNumber('+230 5 765 4321'), 'Nouvelle demande', $createdAt);

        $this->outbox->save($sms);
        $found = $this->outbox->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertSame('+230 5 765 4321', $found->getRecipient()->toString());
        self::assertSame('Nouvelle demande', $found->getText());
        self::assertSame(OutboxStatus::Pending, $found->getStatus());
        self::assertEquals($createdAt, $found->getCreatedAt());
    }

    public function test_should_only_return_pending_messages_ordered_by_creation(): void
    {
        $older = $this->queue('2026-06-17T08:00:00+00:00');
        $newer = $this->queue('2026-06-17T09:00:00+00:00');
        $done = $this->queue('2026-06-17T07:00:00+00:00');
        $done->markSent(new \DateTimeImmutable('2026-06-17T07:05:00+00:00'));
        $this->outbox->save($done);

        $pending = $this->outbox->findPending(10);

        self::assertCount(2, $pending);
        self::assertEquals($older->getId(), $pending[0]->getId());
        self::assertEquals($newer->getId(), $pending[1]->getId());
    }

    private function queue(string $createdAt): OutboxSms
    {
        $sms = OutboxSms::queue(Uuid::v7(), new PhoneNumber('+23057654321'), 'Texte', new \DateTimeImmutable($createdAt));
        $this->outbox->save($sms);

        return $sms;
    }
}
