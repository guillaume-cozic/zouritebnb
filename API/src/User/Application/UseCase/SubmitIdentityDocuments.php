<?php

declare(strict_types=1);

namespace App\User\Application\UseCase;

use App\Shared\Domain\Port\EventBus;
use App\Shared\Domain\Port\UuidGenerator;
use App\User\Domain\Command\SubmitIdentityDocumentsCommand;
use App\User\Domain\Entity\IdentityDocument;
use App\User\Domain\Entity\IdentityDocumentType;
use App\User\Domain\Exception\InvalidIdentityDocumentException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Port\UserRepository;

final readonly class SubmitIdentityDocuments
{
    public function __construct(
        private UserRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    /**
     * @return array{status: string, verifiedAt: ?string, documentType: ?string}
     */
    public function handle(SubmitIdentityDocumentsCommand $command): array
    {
        $user = $this->repository->findById($command->userId);

        if (null === $user) {
            throw UserNotFoundException::becauseNotFound($command->userId->toRfc4122());
        }

        $documentType = IdentityDocumentType::tryFrom($command->documentType)
            ?? throw InvalidIdentityDocumentException::becauseInvalidDocumentType($command->documentType);

        $document = new IdentityDocument(
            content: $command->documentContent,
            originalName: $command->documentOriginalName,
            mimeType: $command->documentMimeType,
            size: $command->documentSize,
        );

        $selfie = new IdentityDocument(
            content: $command->selfieContent,
            originalName: $command->selfieOriginalName,
            mimeType: $command->selfieMimeType,
            size: $command->selfieSize,
        );

        $user->submitAndVerifyIdentity(
            documentId: UuidGenerator::generate(),
            selfieId: UuidGenerator::generate(),
            documentType: $documentType,
            document: $document,
            selfie: $selfie,
            verifiedAt: new \DateTimeImmutable(),
        );

        $this->repository->save($user);
        $this->eventBus->dispatch($user->releaseEvents());

        return [
            'status' => $user->getVerificationStatus()->value,
            'verifiedAt' => $user->getVerifiedAt()?->format(\DateTimeInterface::ATOM),
            'documentType' => $user->getDocumentType()?->value,
        ];
    }
}
