<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Application\UseCase;

use App\Conversation\Application\UseCase\GetConversation;
use App\Conversation\Domain\Entity\Conversation;
use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Exception\ConversationNotFoundException;
use App\Tests\Unit\Conversation\Infrastructure\InMemoryConversationRepository;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class GetConversationTest extends TestCase
{
    private InMemoryConversationRepository $repository;
    private GetConversation $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryConversationRepository();
        $this->useCase = new GetConversation($this->repository);
    }

    public function test_should_return_conversation_by_id(): void
    {
        $conversationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');
        $conversation = new Conversation(
            id: new ConversationId($conversationId),
            reservationId: Uuid::v7(),
            accommodationId: Uuid::v7(),
            teamId: Uuid::v7(),
            guestUserId: Uuid::v7(),
            createdAt: new \DateTimeImmutable('2026-05-14T09:00:00+00:00'),
        );
        $this->repository->save($conversation);

        $result = $this->useCase->byId($conversationId->toRfc4122());

        self::assertSame($conversation, $result);
    }

    public function test_should_throw_when_conversation_not_found(): void
    {
        $missingId = '01961e2f-dead-7000-beef-0000000000ff';

        $this->expectException(ConversationNotFoundException::class);
        $this->expectExceptionMessage(\sprintf('Conversation "%s" not found.', $missingId));

        $this->useCase->byId($missingId);
    }
}
