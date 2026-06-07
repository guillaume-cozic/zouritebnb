<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Post;
use App\Conversation\Application\UseCase\SendMessage;
use App\Conversation\Domain\Entity\Conversation;
use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Port\ConversationRepository;
use App\Conversation\Infrastructure\ApiPlatform\MessageOutput;
use App\Conversation\Infrastructure\ApiPlatform\SendMessageInput;
use App\Conversation\Infrastructure\ApiPlatform\SendMessageProcessor;
use App\Shared\Domain\Port\TransactionManager;
use App\Shared\Domain\Port\UuidGenerator;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\Tests\Unit\Conversation\Infrastructure\FixedClock;
use App\Tests\Unit\Conversation\Infrastructure\InMemoryConversationRepository;
use App\Tests\Unit\Conversation\Infrastructure\InMemoryTeamMembershipChecker;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SendMessageProcessorTest extends TestCase
{
    private InMemoryConversationRepository $repository;
    private InMemoryTeamMembershipChecker $teamMembershipChecker;
    private InMemoryEventBus $eventBus;
    private TransactionalUseCaseHandler $handler;
    private SendMessage $sendMessage;
    private Uuid $conversationId;
    private Uuid $guestUserId;

    #[Before]
    public function init(): void
    {
        $this->repository = new InMemoryConversationRepository();
        $this->teamMembershipChecker = new InMemoryTeamMembershipChecker();
        $this->eventBus = new InMemoryEventBus();
        $clock = new FixedClock(new \DateTimeImmutable('2026-05-14T10:00:00+00:00'));
        $this->sendMessage = new SendMessage($this->repository, $this->teamMembershipChecker, $clock, $this->eventBus);

        // Synchronous transaction manager: just invokes the operation.
        $transactionManager = new class implements TransactionManager {
            public function transactional(callable $operation): mixed
            {
                return $operation();
            }
        };
        $this->handler = new TransactionalUseCaseHandler($transactionManager);

        $this->conversationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');
        $this->guestUserId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000c1');

        $this->repository->save(new Conversation(
            id: new ConversationId($this->conversationId),
            reservationId: Uuid::v7(),
            accommodationId: Uuid::v7(),
            teamId: Uuid::fromString('01961e2f-dead-7000-beef-0000000000b1'),
            guestUserId: $this->guestUserId,
            createdAt: new \DateTimeImmutable('2026-05-14T09:00:00+00:00'),
        ));
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_return_the_created_message_output(): void
    {
        $processor = new SendMessageProcessor($this->sendMessage, $this->repository, $this->handler);

        $output = $processor->process(
            new SendMessageInput(authorUserId: $this->guestUserId->toRfc4122(), body: 'Bonjour'),
            new Post(),
            ['id' => $this->conversationId->toRfc4122()],
        );

        self::assertInstanceOf(MessageOutput::class, $output);
        self::assertSame('Bonjour', $output->body);
        self::assertSame($this->guestUserId->toRfc4122(), $output->authorUserId);
        self::assertFalse($output->isSystem);
    }

    public function test_should_throw_when_created_message_cannot_be_reloaded(): void
    {
        // The processor reloads the conversation through a repository that returns a
        // brand-new conversation with no messages, so the reload loop never finds the
        // returned message id and the defensive guard fires.
        $freshConversation = new Conversation(
            id: new ConversationId($this->conversationId),
            reservationId: Uuid::v7(),
            accommodationId: Uuid::v7(),
            teamId: Uuid::fromString('01961e2f-dead-7000-beef-0000000000b1'),
            guestUserId: $this->guestUserId,
            createdAt: new \DateTimeImmutable('2026-05-14T09:00:00+00:00'),
        );
        $emptyReloadRepository = new class($freshConversation) implements ConversationRepository {
            public function __construct(private readonly Conversation $empty)
            {
            }

            public function save(Conversation $conversation): void
            {
            }

            public function ofId(ConversationId $id): ?Conversation
            {
                return $this->empty;
            }

            public function ofReservationId(Uuid $reservationId): ?Conversation
            {
                return null;
            }

            public function listForGuestUser(Uuid $userId): array
            {
                return [];
            }

            public function listForTeam(Uuid $teamId): array
            {
                return [];
            }
        };

        $processor = new SendMessageProcessor($this->sendMessage, $emptyReloadRepository, $this->handler);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Message was created but could not be reloaded.');

        $processor->process(
            new SendMessageInput(authorUserId: $this->guestUserId->toRfc4122(), body: 'Bonjour'),
            new Post(),
            ['id' => $this->conversationId->toRfc4122()],
        );
    }
}
