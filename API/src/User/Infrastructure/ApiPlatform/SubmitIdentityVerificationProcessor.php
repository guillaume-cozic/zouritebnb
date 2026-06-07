<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\User\Application\UseCase\SubmitIdentityDocuments;
use App\User\Domain\Command\SubmitIdentityDocumentsCommand;
use App\User\Domain\Exception\InvalidIdentityDocumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<mixed, UserVerificationOutput>
 */
final readonly class SubmitIdentityVerificationProcessor implements ProcessorInterface
{
    public function __construct(
        private SubmitIdentityDocuments $submitIdentityDocuments,
        private TransactionalUseCaseHandler $handler,
        private RequestStack $requestStack,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserVerificationOutput
    {
        $request = $this->requestStack->getCurrentRequest();

        $document = $request?->files->get('document');
        $selfie = $request?->files->get('selfie');

        if (!$document instanceof UploadedFile) {
            throw InvalidIdentityDocumentException::becauseFileMissing('document');
        }

        if (!$selfie instanceof UploadedFile) {
            throw InvalidIdentityDocumentException::becauseFileMissing('selfie');
        }

        $documentType = (string) $request?->request->get('documentType', '');

        /** @var array{status: string, verifiedAt: ?string, documentType: ?string} $result */
        $result = $this->handler->execute(fn () => $this->submitIdentityDocuments->handle(new SubmitIdentityDocumentsCommand(
            userId: Uuid::fromString($uriVariables['id']),
            documentType: $documentType,
            documentContent: $document->getContent(),
            documentOriginalName: $document->getClientOriginalName(),
            documentMimeType: $document->getClientMimeType() ?? '',
            documentSize: (int) $document->getSize(),
            selfieContent: $selfie->getContent(),
            selfieOriginalName: $selfie->getClientOriginalName(),
            selfieMimeType: $selfie->getClientMimeType() ?? '',
            selfieSize: (int) $selfie->getSize(),
        )));

        $output = new UserVerificationOutput();
        $output->userId = $uriVariables['id'];
        $output->status = $result['status'];
        $output->documentType = $result['documentType'];
        $output->verifiedAt = $result['verifiedAt'];

        return $output;
    }
}
