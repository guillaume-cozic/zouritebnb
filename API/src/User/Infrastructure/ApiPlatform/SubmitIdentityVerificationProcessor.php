<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\User\Application\UseCase\SubmitIdentityDocuments;
use App\User\Domain\Command\SubmitIdentityDocumentsCommand;
use App\User\Domain\Exception\InvalidIdentityDocumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserVerificationOutput
    {
        // Object-level authorization: a user may only submit their own KYC. Without
        // this check, any authenticated user could verify (or pollute) another
        // user's identity by putting that user's id in the URL.
        $targetId = Uuid::fromString($uriVariables['id']);
        if (!$this->currentUser->id()->equals($targetId)) {
            throw new AccessDeniedHttpException('You can only submit your own identity verification.');
        }

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
            userId: $targetId,
            documentType: $documentType,
            documentContent: $document->getContent(),
            documentOriginalName: $document->getClientOriginalName(),
            // Real MIME sniffed from the bytes (finfo), not the client-supplied
            // Content-Type header which an attacker fully controls.
            documentMimeType: $document->getMimeType() ?? '',
            documentSize: (int) $document->getSize(),
            selfieContent: $selfie->getContent(),
            selfieOriginalName: $selfie->getClientOriginalName(),
            selfieMimeType: $selfie->getMimeType() ?? '',
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
