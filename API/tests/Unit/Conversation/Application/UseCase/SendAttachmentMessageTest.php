<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Application\UseCase;

use App\Conversation\Application\UseCase\SendAttachmentMessage;
use App\Conversation\Domain\Command\SendAttachmentMessageCommand;
use App\Conversation\Domain\Entity\Conversation;
use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Exception\ConversationNotFoundException;
use App\Conversation\Domain\Exception\ConversationParticipantException;
use App\Conversation\Domain\Exception\InvalidAttachmentException;
use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\Conversation\Infrastructure\FakeAttachmentImageTransformer;
use App\Tests\Unit\Conversation\Infrastructure\FixedClock;
use App\Tests\Unit\Conversation\Infrastructure\InMemoryAttachmentStorage;
use App\Tests\Unit\Conversation\Infrastructure\InMemoryConversationRepository;
use App\Tests\Unit\Conversation\Infrastructure\InMemoryTeamMembershipChecker;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SendAttachmentMessageTest extends TestCase
{
    private InMemoryConversationRepository $repository;
    private InMemoryTeamMembershipChecker $teamMembershipChecker;
    private InMemoryAttachmentStorage $storage;
    private InMemoryEventBus $eventBus;
    private SendAttachmentMessage $useCase;
    private Uuid $conversationId;
    private Uuid $teamId;
    private Uuid $guestUserId;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryConversationRepository();
        $this->teamMembershipChecker = new InMemoryTeamMembershipChecker();
        $this->storage = new InMemoryAttachmentStorage();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new SendAttachmentMessage(
            $this->repository,
            $this->teamMembershipChecker,
            new FakeAttachmentImageTransformer(),
            $this->storage,
            new FixedClock(new \DateTimeImmutable('2026-05-14T10:00:00+00:00')),
            $this->eventBus,
        );

        $this->conversationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');
        $this->teamId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000b1');
        $this->guestUserId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000c1');

        $this->repository->save(new Conversation(
            id: new ConversationId($this->conversationId),
            reservationId: Uuid::v7(),
            accommodationId: Uuid::v7(),
            teamId: $this->teamId,
            guestUserId: $this->guestUserId,
            createdAt: new \DateTimeImmutable('2026-05-14T09:00:00+00:00'),
        ));
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    private function command(
        ?string $conversationId = null,
        ?string $authorUserId = null,
        ?string $body = null,
        string $mimeType = 'image/jpeg',
        int $size = 1000,
    ): SendAttachmentMessageCommand {
        return new SendAttachmentMessageCommand(
            conversationId: $conversationId ?? $this->conversationId->toRfc4122(),
            authorUserId: $authorUserId ?? $this->guestUserId->toRfc4122(),
            body: $body,
            content: 'raw-image-bytes',
            mimeType: $mimeType,
            size: $size,
        );
    }

    public function test_should_let_guest_post_photo_without_caption(): void
    {
        $this->useCase->handle($this->command());

        $conversation = $this->repository->ofId(new ConversationId($this->conversationId));
        self::assertCount(1, $conversation->getMessages());
        $message = $conversation->getMessages()[0];
        self::assertFalse($message->isSystem());
        self::assertNull($message->getBody());
        self::assertTrue($this->guestUserId->equals($message->getAuthorUserId()));

        $filename = $message->getAttachment()->filename();
        self::assertStringEndsWith('.webp', $filename);
        self::assertSame('webp:raw-image-bytes', $this->storage->get($filename));
    }

    public function test_should_keep_caption_as_message_body(): void
    {
        $this->useCase->handle($this->command(body: 'Voici la photo !'));

        $conversation = $this->repository->ofId(new ConversationId($this->conversationId));
        $message = $conversation->getMessages()[0];
        self::assertSame('Voici la photo !', $message->getBody()->toString());
        self::assertNotNull($message->getAttachment());
    }

    public function test_should_ignore_blank_caption(): void
    {
        $this->useCase->handle($this->command(body: '   '));

        $conversation = $this->repository->ofId(new ConversationId($this->conversationId));
        self::assertNull($conversation->getMessages()[0]->getBody());
    }

    public function test_should_let_team_member_post_photo(): void
    {
        $teamMember = Uuid::fromString('01961e2f-dead-7000-beef-0000000000d1');
        $this->teamMembershipChecker->add($teamMember, $this->teamId);

        $this->useCase->handle($this->command(authorUserId: $teamMember->toRfc4122()));

        $conversation = $this->repository->ofId(new ConversationId($this->conversationId));
        $message = $conversation->getMessages()[0];
        self::assertTrue($teamMember->equals($message->getAuthorUserId()));
        self::assertNotNull($message->getAttachment());
    }

    public function test_should_reject_outsider_without_storing_file(): void
    {
        try {
            $this->useCase->handle($this->command(authorUserId: Uuid::v7()->toRfc4122()));
            self::fail('Expected ConversationParticipantException.');
        } catch (ConversationParticipantException) {
        }

        self::assertSame(0, $this->storage->count());
    }

    public function test_should_throw_not_found_for_unknown_conversation(): void
    {
        $this->expectException(ConversationNotFoundException::class);

        $this->useCase->handle($this->command(conversationId: Uuid::v7()->toRfc4122()));
    }

    #[DataProvider('provideInvalidMimeTypes')]
    public function test_should_reject_invalid_mime_type(string $mimeType): void
    {
        $this->expectException(InvalidAttachmentException::class);

        $this->useCase->handle($this->command(mimeType: $mimeType));
    }

    public static function provideInvalidMimeTypes(): \Generator
    {
        yield 'plain text' => ['text/plain'];
        yield 'pdf' => ['application/pdf'];
        yield 'svg' => ['image/svg+xml'];
        yield 'empty' => [''];
    }

    public function test_should_reject_too_large_file(): void
    {
        $this->expectException(InvalidAttachmentException::class);

        $this->useCase->handle($this->command(size: 10 * 1024 * 1024 + 1));
    }
}
