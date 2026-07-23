<?php

declare(strict_types=1);

namespace App\Tests\Unit\Contact\Application\UseCase;

use App\Contact\Application\UseCase\SendContactMessage;
use App\Contact\Domain\Command\SendContactMessageCommand;
use App\Contact\Domain\Event\ContactMessageSent;
use App\Contact\Domain\Exception\InvalidContactMessageException;
use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\Contact\Infrastructure\FixedClock;
use App\Tests\Unit\Contact\Infrastructure\InMemoryContactMessageRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SendContactMessageTest extends TestCase
{
    private const string NOW = '2026-07-23 10:00:00';

    private InMemoryContactMessageRepository $repository;
    private InMemoryEventBus $eventBus;
    private SendContactMessage $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryContactMessageRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new SendContactMessage(
            $this->repository,
            $this->eventBus,
            new FixedClock(new \DateTimeImmutable(self::NOW)),
        );
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_store_contact_message_and_dispatch_event(): void
    {
        $contactMessageId = Uuid::fromString('01981e2f-beef-7000-dead-000000000001');
        UuidGenerator::freeze($contactMessageId);

        $this->useCase->handle(new SendContactMessageCommand(
            name: 'Jeanne Dupont',
            email: 'jeanne.dupont@example.com',
            subject: 'Question about a booking',
            message: 'Hello, I would like to know more about the cancellation policy.',
        ));

        $contactMessage = $this->repository->findById($contactMessageId);
        self::assertNotNull($contactMessage);
        self::assertSame('Jeanne Dupont', $contactMessage->getName());
        self::assertSame('jeanne.dupont@example.com', $contactMessage->getEmail());
        self::assertSame('Question about a booking', $contactMessage->getSubject());
        self::assertSame('Hello, I would like to know more about the cancellation policy.', $contactMessage->getMessage());
        self::assertSame(self::NOW, $contactMessage->getSentAt()->format('Y-m-d H:i:s'));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ContactMessageSent::class, $events[0]);
        self::assertTrue($contactMessageId->equals($events[0]->contactMessageId));
    }

    #[DataProvider('invalidContactMessages')]
    public function test_should_not_send_contact_message_with_invalid_data(
        string $name,
        string $email,
        string $subject,
        string $message,
        string $expectedMessage,
    ): void {
        $this->expectException(InvalidContactMessageException::class);
        $this->expectExceptionMessage($expectedMessage);

        try {
            $this->useCase->handle(new SendContactMessageCommand(
                name: $name,
                email: $email,
                subject: $subject,
                message: $message,
            ));
        } finally {
            self::assertCount(0, $this->repository->all());
            self::assertCount(0, $this->eventBus->getDispatchedEvents());
        }
    }

    public static function invalidContactMessages(): \Generator
    {
        yield 'empty name' => [
            '',
            'jeanne.dupont@example.com',
            'Question',
            'Hello there.',
            'Name is required.',
        ];

        yield 'blank name' => [
            '   ',
            'jeanne.dupont@example.com',
            'Question',
            'Hello there.',
            'Name is required.',
        ];

        yield 'invalid email' => [
            'Jeanne Dupont',
            'not-an-email',
            'Question',
            'Hello there.',
            'Email "not-an-email" is not a valid email address.',
        ];

        yield 'empty email' => [
            'Jeanne Dupont',
            '',
            'Question',
            'Hello there.',
            'Email "" is not a valid email address.',
        ];

        yield 'empty subject' => [
            'Jeanne Dupont',
            'jeanne.dupont@example.com',
            '',
            'Hello there.',
            'Subject is required.',
        ];

        yield 'blank subject' => [
            'Jeanne Dupont',
            'jeanne.dupont@example.com',
            '   ',
            'Hello there.',
            'Subject is required.',
        ];

        yield 'empty message' => [
            'Jeanne Dupont',
            'jeanne.dupont@example.com',
            'Question',
            '',
            'Message is required.',
        ];

        yield 'blank message' => [
            'Jeanne Dupont',
            'jeanne.dupont@example.com',
            'Question',
            '   ',
            'Message is required.',
        ];
    }
}
