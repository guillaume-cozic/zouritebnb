<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\UseCase;

use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use App\Tests\Unit\User\Infrastructure\InMemoryUserRepository;
use App\User\Application\UseCase\SubmitIdentityDocuments;
use App\User\Domain\Command\SubmitIdentityDocumentsCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\VerificationStatus;
use App\User\Domain\Event\IdentityVerified;
use App\User\Domain\Exception\IdentityVerificationException;
use App\User\Domain\Exception\InvalidIdentityDocumentException;
use App\User\Domain\Exception\UserNotFoundException;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SubmitIdentityDocumentsTest extends TestCase
{
    private InMemoryUserRepository $repository;
    private InMemoryEventBus $eventBus;
    private SubmitIdentityDocuments $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new SubmitIdentityDocuments($this->repository, $this->eventBus);
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    private function persistUser(): string
    {
        $user = new User(
            id: Uuid::v7(),
            email: 'john@example.com',
            hashedPassword: 'hashed',
            teamId: Uuid::v7(),
        );
        $this->repository->save($user);

        return $user->getId()->toRfc4122();
    }

    private function command(string $userId, string $documentType = 'passport', string $mimeType = 'image/jpeg'): SubmitIdentityDocumentsCommand
    {
        return new SubmitIdentityDocumentsCommand(
            userId: Uuid::fromString($userId),
            documentType: $documentType,
            documentContent: 'doc-bytes',
            documentOriginalName: 'passport.jpg',
            documentMimeType: $mimeType,
            documentSize: 100,
            selfieContent: 'selfie-bytes',
            selfieOriginalName: 'selfie.jpg',
            selfieMimeType: 'image/jpeg',
            selfieSize: 50,
        );
    }

    public function test_should_verify_user_and_dispatch_event(): void
    {
        $documentId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000aa');
        $selfieId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000bb');
        UuidGenerator::queue([$documentId, $selfieId]);

        $userId = $this->persistUser();

        $result = $this->useCase->handle($this->command($userId));

        self::assertSame('verified', $result['status']);
        self::assertSame('passport', $result['documentType']);
        self::assertNotNull($result['verifiedAt']);

        $user = $this->repository->findById(Uuid::fromString($userId));
        self::assertNotNull($user);
        self::assertSame(VerificationStatus::Verified, $user->getVerificationStatus());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(IdentityVerified::class, $events[0]);
        self::assertSame($documentId->toRfc4122().'.jpg', $events[0]->documentFilename);
        self::assertSame($selfieId->toRfc4122().'.jpg', $events[0]->selfieFilename);
    }

    public function test_should_fail_when_user_not_found(): void
    {
        $this->expectException(UserNotFoundException::class);

        try {
            $this->useCase->handle($this->command(Uuid::v7()->toRfc4122()));
        } finally {
            self::assertCount(0, $this->eventBus->getDispatchedEvents());
        }
    }

    public function test_should_fail_with_invalid_document_type(): void
    {
        $userId = $this->persistUser();

        $this->expectException(InvalidIdentityDocumentException::class);
        $this->useCase->handle($this->command($userId, documentType: 'unknown'));
    }

    public function test_should_fail_with_invalid_mime_type(): void
    {
        $userId = $this->persistUser();

        $this->expectException(InvalidIdentityDocumentException::class);
        $this->useCase->handle($this->command($userId, mimeType: 'application/pdf'));
    }

    public function test_should_fail_when_already_verified(): void
    {
        $userId = $this->persistUser();
        $this->useCase->handle($this->command($userId));

        $this->expectException(IdentityVerificationException::class);
        $this->useCase->handle($this->command($userId));
    }
}
