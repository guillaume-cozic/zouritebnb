<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Application\UseCase;

use App\Notification\Application\UseCase\QueueEmail;
use App\Notification\Domain\Command\QueueEmailCommand;
use App\Notification\Domain\Entity\OutboxStatus;
use App\Notification\Domain\Exception\InvalidEmailAddressException;
use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\Notification\Infrastructure\FakeEmailRenderer;
use App\Tests\Unit\Notification\Infrastructure\FixedClock;
use App\Tests\Unit\Notification\Infrastructure\InMemoryEmailOutbox;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class QueueEmailTest extends TestCase
{
    private InMemoryEmailOutbox $outbox;
    private QueueEmail $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->outbox = new InMemoryEmailOutbox();
        $this->useCase = new QueueEmail(
            $this->outbox,
            new FakeEmailRenderer(),
            new FixedClock(new \DateTimeImmutable('2026-06-16 09:00:00')),
        );
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_render_the_view_and_persist_a_pending_email(): void
    {
        $id = Uuid::fromString('01961e2f-beef-7000-dead-000000000001');
        UuidGenerator::freeze($id);

        $this->useCase->handle(new QueueEmailCommand(
            recipientEmail: 'marie@example.com',
            recipientName: 'Marie',
            subject: 'Bienvenue sur BnB Rodrigues',
            template: 'emails/traveler/welcome.html.twig',
            variables: ['greetingName' => 'Marie'],
        ));

        $email = $this->outbox->findById($id);
        self::assertNotNull($email);
        self::assertSame('marie@example.com', $email->getRecipient()->toString());
        self::assertSame('Marie', $email->getRecipientName());
        self::assertSame('Bienvenue sur BnB Rodrigues', $email->getSubject());
        self::assertStringContainsString('emails/traveler/welcome.html.twig', $email->getHtmlBody());
        self::assertStringContainsString('Marie', $email->getHtmlBody());
        self::assertSame(OutboxStatus::Pending, $email->getStatus());
        self::assertSame(0, $email->getAttempts());
        self::assertEquals(new \DateTimeImmutable('2026-06-16 09:00:00'), $email->getCreatedAt());
    }

    public function test_should_reject_an_invalid_recipient_address(): void
    {
        $this->expectException(InvalidEmailAddressException::class);

        $this->useCase->handle(new QueueEmailCommand(
            recipientEmail: 'not-an-email',
            recipientName: null,
            subject: 'Hello',
            template: 'emails/traveler/welcome.html.twig',
            variables: [],
        ));
    }
}
