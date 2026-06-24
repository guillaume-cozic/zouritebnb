<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\User\Application\UseCase\UploadHostAvatar;
use App\User\Domain\Command\UploadHostAvatarCommand;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProcessorInterface<mixed, HostAvatarOutput>
 */
final readonly class UploadHostAvatarProcessor implements ProcessorInterface
{
    public function __construct(
        private UploadHostAvatar $useCase,
        private TransactionalUseCaseHandler $handler,
        private RequestStack $requestStack,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): HostAvatarOutput
    {
        $request = $this->requestStack->getCurrentRequest();
        $file = $request?->files->get('file');

        if (!$file instanceof UploadedFile) {
            throw new \InvalidArgumentException('No file uploaded.');
        }

        $filename = $this->handler->execute(fn (): string => $this->useCase->handle(new UploadHostAvatarCommand(
            userId: $this->currentUser->id(),
            content: $file->getContent(),
            // Real MIME sniffed from the bytes (finfo), not the client-supplied Content-Type.
            mimeType: $file->getMimeType() ?? '',
            size: $file->getSize() ?? 0,
        )));

        $output = new HostAvatarOutput();
        $output->avatarUrl = '/uploads/photos/'.$filename;

        return $output;
    }
}
