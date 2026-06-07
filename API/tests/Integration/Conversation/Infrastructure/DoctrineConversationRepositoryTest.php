<?php

declare(strict_types=1);

namespace App\Tests\Integration\Conversation\Infrastructure;

use App\Conversation\Domain\Entity\Conversation;
use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Entity\MessageBody;
use App\Conversation\Domain\Entity\MessageId;
use App\Conversation\Domain\Port\ConversationRepository;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineConversationRepositoryTest extends RepositoryTestCase
{
    private ConversationRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(ConversationRepository::class);
    }

    public function test_should_save_and_find_by_id(): void
    {
        $id = new ConversationId(Uuid::v4());
        $reservationId = Uuid::v4();
        $accommodationId = Uuid::v4();
        $teamId = Uuid::v4();
        $guestUserId = Uuid::v4();
        $createdAt = new \DateTimeImmutable('2026-01-01 10:00:00');

        $conversation = Conversation::start(
            id: $id,
            reservationId: $reservationId,
            accommodationId: $accommodationId,
            teamId: $teamId,
            guestUserId: $guestUserId,
            openingMessageId: new MessageId(Uuid::v4()),
            openingMessageBody: new MessageBody('Welcome to the conversation'),
            createdAt: $createdAt,
        );

        $this->repository->save($conversation);
        $found = $this->repository->ofId($id);

        self::assertNotNull($found);
        self::assertSame($id->toString(), $found->getId()->toString());
        self::assertTrue($found->getReservationId()->equals($reservationId));
        self::assertTrue($found->getAccommodationId()->equals($accommodationId));
        self::assertTrue($found->getTeamId()->equals($teamId));
        self::assertTrue($found->getGuestUserId()->equals($guestUserId));
        self::assertEquals($createdAt, $found->getCreatedAt());

        $messages = $found->getMessages();
        self::assertCount(1, $messages);
        self::assertSame('Welcome to the conversation', $messages[0]->getBody()->toString());
        self::assertTrue($messages[0]->isSystem());
        self::assertNull($messages[0]->getAuthorUserId());
    }

    public function test_should_return_null_when_not_found_by_id(): void
    {
        $result = $this->repository->ofId(new ConversationId(Uuid::v4()));

        self::assertNull($result);
    }

    public function test_should_find_by_reservation_id(): void
    {
        $reservationId = Uuid::v4();
        $conversation = $this->aConversation(reservationId: $reservationId);
        $this->repository->save($conversation);

        $found = $this->repository->ofReservationId($reservationId);

        self::assertNotNull($found);
        self::assertTrue($found->getReservationId()->equals($reservationId));
    }

    public function test_should_return_null_when_not_found_by_reservation_id(): void
    {
        $result = $this->repository->ofReservationId(Uuid::v4());

        self::assertNull($result);
    }

    public function test_should_append_new_messages_on_update(): void
    {
        $id = new ConversationId(Uuid::v4());
        $conversation = Conversation::start(
            id: $id,
            reservationId: Uuid::v4(),
            accommodationId: Uuid::v4(),
            teamId: Uuid::v4(),
            guestUserId: $guestUserId = Uuid::v4(),
            openingMessageId: new MessageId(Uuid::v4()),
            openingMessageBody: new MessageBody('Opening message'),
            createdAt: new \DateTimeImmutable('2026-01-01 10:00:00'),
        );
        $this->repository->save($conversation);

        $conversation->postGuestMessage(
            new MessageId(Uuid::v4()),
            new MessageBody('Hello from the guest'),
            new \DateTimeImmutable('2026-01-01 11:00:00'),
        );
        $conversation->postHostMessage(
            new MessageId(Uuid::v4()),
            new MessageBody('Hello from the host'),
            $hostUserId = Uuid::v4(),
            new \DateTimeImmutable('2026-01-01 12:00:00'),
        );
        $this->repository->save($conversation);

        $found = $this->repository->ofId($id);

        self::assertNotNull($found);
        $messages = $found->getMessages();
        self::assertCount(3, $messages);

        // Ordered by sentAt ASC.
        self::assertSame('Opening message', $messages[0]->getBody()->toString());
        self::assertTrue($messages[0]->isSystem());

        self::assertSame('Hello from the guest', $messages[1]->getBody()->toString());
        self::assertFalse($messages[1]->isSystem());
        self::assertNotNull($messages[1]->getAuthorUserId());
        self::assertTrue($messages[1]->getAuthorUserId()->equals($guestUserId));

        self::assertSame('Hello from the host', $messages[2]->getBody()->toString());
        self::assertFalse($messages[2]->isSystem());
        self::assertNotNull($messages[2]->getAuthorUserId());
        self::assertTrue($messages[2]->getAuthorUserId()->equals($hostUserId));
    }

    public function test_should_not_duplicate_existing_messages_on_update(): void
    {
        $id = new ConversationId(Uuid::v4());
        $conversation = $this->aConversation(id: $id);
        $this->repository->save($conversation);

        // Saving again without new messages must not duplicate the existing ones.
        $this->repository->save($conversation);

        $found = $this->repository->ofId($id);

        self::assertNotNull($found);
        self::assertCount(1, $found->getMessages());
    }

    public function test_should_update_scalar_fields_on_existing_conversation(): void
    {
        $id = new ConversationId(Uuid::v4());
        $conversation = $this->aConversation(id: $id);
        $this->repository->save($conversation);

        // Persisting the same aggregate must update the existing row, not create a new one.
        $this->repository->save($conversation);

        $found = $this->repository->ofId($id);

        self::assertNotNull($found);
        self::assertSame($id->toString(), $found->getId()->toString());
    }

    public function test_should_list_for_guest_user_ordered_by_created_at_desc(): void
    {
        $guestUserId = Uuid::v4();

        $older = $this->aConversation(
            guestUserId: $guestUserId,
            createdAt: new \DateTimeImmutable('2026-01-01 10:00:00'),
        );
        $newer = $this->aConversation(
            guestUserId: $guestUserId,
            createdAt: new \DateTimeImmutable('2026-02-01 10:00:00'),
        );
        $otherUser = $this->aConversation(
            guestUserId: Uuid::v4(),
            createdAt: new \DateTimeImmutable('2026-03-01 10:00:00'),
        );

        $this->repository->save($older);
        $this->repository->save($newer);
        $this->repository->save($otherUser);

        $result = $this->repository->listForGuestUser($guestUserId);

        self::assertCount(2, $result);
        self::assertSame($newer->getId()->toString(), $result[0]->getId()->toString());
        self::assertSame($older->getId()->toString(), $result[1]->getId()->toString());
    }

    public function test_should_return_empty_list_for_guest_user_without_conversations(): void
    {
        $result = $this->repository->listForGuestUser(Uuid::v4());

        self::assertSame([], $result);
    }

    public function test_should_list_for_team_ordered_by_created_at_desc(): void
    {
        $teamId = Uuid::v4();

        $older = $this->aConversation(
            teamId: $teamId,
            createdAt: new \DateTimeImmutable('2026-01-01 10:00:00'),
        );
        $newer = $this->aConversation(
            teamId: $teamId,
            createdAt: new \DateTimeImmutable('2026-02-01 10:00:00'),
        );
        $otherTeam = $this->aConversation(
            teamId: Uuid::v4(),
            createdAt: new \DateTimeImmutable('2026-03-01 10:00:00'),
        );

        $this->repository->save($older);
        $this->repository->save($newer);
        $this->repository->save($otherTeam);

        $result = $this->repository->listForTeam($teamId);

        self::assertCount(2, $result);
        self::assertSame($newer->getId()->toString(), $result[0]->getId()->toString());
        self::assertSame($older->getId()->toString(), $result[1]->getId()->toString());
    }

    public function test_should_return_empty_list_for_team_without_conversations(): void
    {
        $result = $this->repository->listForTeam(Uuid::v4());

        self::assertSame([], $result);
    }

    private function aConversation(
        ?ConversationId $id = null,
        ?Uuid $reservationId = null,
        ?Uuid $accommodationId = null,
        ?Uuid $teamId = null,
        ?Uuid $guestUserId = null,
        ?\DateTimeImmutable $createdAt = null,
    ): Conversation {
        return Conversation::start(
            id: $id ?? new ConversationId(Uuid::v4()),
            reservationId: $reservationId ?? Uuid::v4(),
            accommodationId: $accommodationId ?? Uuid::v4(),
            teamId: $teamId ?? Uuid::v4(),
            guestUserId: $guestUserId ?? Uuid::v4(),
            openingMessageId: new MessageId(Uuid::v4()),
            openingMessageBody: new MessageBody('Opening message'),
            createdAt: $createdAt ?? new \DateTimeImmutable('2026-01-01 10:00:00'),
        );
    }
}
