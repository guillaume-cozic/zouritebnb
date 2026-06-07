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

    public function test_should_return_guest_conversations_for_user_without_team(): void
    {
        $guestUserId = Uuid::v7();
        $teamId = Uuid::v7();
        $expected = $this->givenConversation($teamId, $guestUserId);

        $result = $this->useCase->forUser($guestUserId);

        self::assertCount(1, $result);
        self::assertSame($expected->getId()->toString(), $result[0]->getId()->toString());
    }

    public function test_should_return_team_conversations_for_team_member(): void
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

    public function test_should_merge_and_deduplicate_guest_and_team_conversations(): void
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

    public function test_should_return_team_conversations_for_team(): void
    {
        $teamId = Uuid::v7();
        $expected = $this->givenConversation($teamId, Uuid::v7());
        $this->givenConversation(Uuid::v7(), Uuid::v7()); // unrelated team

        $result = $this->useCase->forTeam($teamId);

        self::assertCount(1, $result);
        self::assertSame($expected->getId()->toString(), $result[0]->getId()->toString());
    }

    public function test_should_return_empty_array_when_team_has_no_conversations(): void
    {
        $result = $this->useCase->forTeam(Uuid::v7());

        self::assertSame([], $result);
    }

    public function test_should_return_only_guest_conversations_when_user_has_no_team(): void
    {
        $userId = Uuid::v7();
        // No team set for this user -> userTeamProvider returns null -> teamConversations branch is [].
        $expected = $this->givenConversation(Uuid::v7(), $userId);

        $result = $this->useCase->forUser($userId);

        self::assertCount(1, $result);
        self::assertSame($expected->getId()->toString(), $result[0]->getId()->toString());
    }

    public function test_should_sort_by_created_at_descending(): void
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
