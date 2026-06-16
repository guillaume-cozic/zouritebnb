<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Application\Listener;

use App\Notification\Application\Email\HostEmails;
use App\Notification\Application\Listener\SendCoHostInvitationEmailOnCoHostInvited;
use App\Notification\Application\UseCase\QueueEmail;
use App\Shared\Domain\Event\CoHostInvited;
use App\Tests\Unit\Notification\Infrastructure\FakeEmailRenderer;
use App\Tests\Unit\Notification\Infrastructure\FixedClock;
use App\Tests\Unit\Notification\Infrastructure\InMemoryEmailOutbox;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SendCoHostInvitationEmailOnCoHostInvitedTest extends TestCase
{
    private InMemoryEmailOutbox $outbox;
    private SendCoHostInvitationEmailOnCoHostInvited $listener;

    #[Before]
    public function initListener(): void
    {
        $this->outbox = new InMemoryEmailOutbox();
        $this->listener = new SendCoHostInvitationEmailOnCoHostInvited(
            new HostEmails(),
            new QueueEmail($this->outbox, new FakeEmailRenderer(), new FixedClock(new \DateTimeImmutable('2026-06-16 09:00:00'))),
        );
    }

    public function test_should_queue_an_invitation_email_to_the_invited_address(): void
    {
        ($this->listener)(new CoHostInvited(
            invitationId: Uuid::v7(),
            teamId: Uuid::v7(),
            email: 'newcohost@example.com',
        ));

        $queued = $this->outbox->all();
        self::assertCount(1, $queued);
        self::assertSame('newcohost@example.com', $queued[0]->getRecipient()->toString());
        self::assertStringContainsString('co-hôte', $queued[0]->getSubject());
        self::assertStringContainsString('emails/host/cohost_invitation.html.twig', $queued[0]->getHtmlBody());
    }
}
