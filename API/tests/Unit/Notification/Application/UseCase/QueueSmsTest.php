<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Application\UseCase;

use App\Notification\Application\UseCase\QueueSms;
use App\Notification\Domain\Command\QueueSmsCommand;
use App\Notification\Domain\Entity\OutboxStatus;
use App\Notification\Domain\Exception\InvalidPhoneNumberException;
use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\Notification\Infrastructure\FixedClock;
use App\Tests\Unit\Notification\Infrastructure\InMemorySmsOutbox;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class QueueSmsTest extends TestCase
{
    private InMemorySmsOutbox $outbox;
    private QueueSms $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->outbox = new InMemorySmsOutbox();
        $this->useCase = new QueueSms(
            $this->outbox,
            new FixedClock(new \DateTimeImmutable('2026-06-17 09:00:00')),
        );
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_persist_a_pending_sms(): void
    {
        $id = Uuid::fromString('01961e2f-beef-7000-dead-000000000001');
        UuidGenerator::freeze($id);

        $this->useCase->handle(new QueueSmsCommand('+230 5 765 4321', 'Nouvelle demande de réservation'));

        $sms = $this->outbox->findById($id);
        self::assertNotNull($sms);
        self::assertSame('+230 5 765 4321', $sms->getRecipient()->toString());
        self::assertSame('Nouvelle demande de réservation', $sms->getText());
        self::assertSame(OutboxStatus::Pending, $sms->getStatus());
        self::assertSame(0, $sms->getAttempts());
    }

    public function test_should_reject_an_invalid_phone_number(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);

        $this->useCase->handle(new QueueSmsCommand('call-me', 'Hello'));
    }
}
