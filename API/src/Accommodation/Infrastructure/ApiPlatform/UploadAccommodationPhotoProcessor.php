<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\UploadAccommodationPhoto;
use App\Accommodation\Domain\Command\UploadAccommodationPhotoCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<mixed, void>
 */
final readonly class UploadAccommodationPhotoProcessor implements ProcessorInterface
{
    public function __construct(
        private UploadAccommodationPhoto $uploadAccommodationPhoto,
        private TransactionalUseCaseHandler $handler,
        private RequestStack $requestStack,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile) {
            throw new \InvalidArgumentException('No file uploaded.');
        }

        $this->handler->execute(fn () => $this->uploadAccommodationPhoto->handle(new UploadAccommodationPhotoCommand(
            accommodationId: Uuid::fromString($uriVariables['id']),
            content: $file->getContent(),
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getClientMimeType() ?? '',
            size: $file->getSize(),
        )));
    }
}
