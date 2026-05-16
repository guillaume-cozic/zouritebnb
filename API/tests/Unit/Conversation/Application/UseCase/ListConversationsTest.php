<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Application\UseCase;

use App\Conversation\Application\UseCase\ListConversations;
use App\Conversation\Domain\Entity\Conversation;
use App\Conversation\Domain\Entity\ConversationId;
use App\Tests\Unit\Conversation\Infrastructure\InMemoryConversationRepository;
use App\Tests\Unit\Conversation\Infrastructure\InMemoryUserTeamProvider;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ListConversationsTest extends TestCase
{
    private InMemoryConversationRepository $repository;
    private InMemoryUserTeamProvider $userTeamProvider;
    private ListConversations $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryConversationRepository();
        $this->userTeamProvider = new InMemoryUserTeamProvider();
        $this->useCase = new ListConversations($this->repository, $this->userTeamProvider);
    }

    private function givenConversation(Uuid $teamId, Uuid $guestUserId, string $createdAt = '2026-05-14T09:00:00+00:00'): Conversation
    {
        $conversation = new Conversation(
            id: new ConversationId(Uuid::v7()),
            reservationId: Uuid::v7(),
            accommodationId: Uuid::v7(),
            teamId: $teamId,
            guestUserId: $guestUserId,
            createdAt: new \DateTimeImmutable($createdAt),
        );
        $this->repository->save($conversation);

        return $conversation;
    }

    public function testShouldReturnGuestConversationsForUserWithoutTeam(): void
    {
        $guestUserId = Uuid::v7();
        $teamId = Uuid::v7();
        $expected = $this->givenConversation($teamId, $guestUserId);

        $result = $this->useCase->forUser($guestUserId);

        self::assertCount(1, $result);
        self::assertSame($expected->getId()->toString(), $result[0]->getId()->toString());
    }

    public function testShouldReturnTeamConversationsForTeamMember(): void
    {
        $userId = Uuid::v7();
        $teamId = Uuid::v7();
        $this->userTeamProvider->set($userId, $teamId);

        $expected = $this->givenConversation($teamId, Uuid::v7());
        $this->givenConversation(Uuid::v7(), Uuid::v7()); // unrelated

        $result = $this->useCase->forUser($userId);

        self::assertCount(1, $result);
        self::assertSame($expected->getId()->toString(), $result[0]->getId()->toString());
    }

    public function testShouldMergeAndDeduplicateGuestAndTeamConversations(): void
    {
        $userId = Uuid::v7();
        $teamId = Uuid::v7();
        $this->userTeamProvider->set($userId, $teamId);

        // user is BOTH guest and team member of this conversation (edge case)
        $shared = $this->givenConversation($teamId, $userId);
        $teamOnly = $this->givenConversation($teamId, Uuid::v7());
        $guestOnly = $this->givenConversation(Uuid::v7(), $userId);

        $result = $this->useCase->forUser($userId);

        self::assertCount(3, $result);
        $ids = array_map(static fn ($c) => $c->getId()->toString(), $result);
        self::assertContains($shared->getId()->toString(), $ids);
        self::assertContains($teamOnly->getId()->toString(), $ids);
        self::assertContains($guestOnly->getId()->toString(), $ids);
    }

    public function testShouldSortByCreatedAtDescending(): void
    {
        $userId = Uuid::v7();
        $oldest = $this->givenConversation(Uuid::v7(), $userId, '2026-05-10T00:00:00+00:00');
        $newest = $this->givenConversation(Uuid::v7(), $userId, '2026-05-14T00:00:00+00:00');
        $middle = $this->givenConversation(Uuid::v7(), $userId, '2026-05-12T00:00:00+00:00');

        $result = $this->useCase->forUser($userId);

        self::assertSame($newest->getId()->toString(), $result[0]->getId()->toString());
        self::assertSame($middle->getId()->toString(), $result[1]->getId()->toString());
        self::assertSame($oldest->getId()->toString(), $result[2]->getId()->toString());
    }
}
