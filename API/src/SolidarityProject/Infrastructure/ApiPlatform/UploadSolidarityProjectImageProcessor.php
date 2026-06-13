<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\SolidarityProject\Application\UseCase\UploadSolidarityProjectImage;
use App\SolidarityProject\Domain\Command\UploadSolidarityProjectImageCommand;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProcessorInterface<mixed, AdminSolidarityProjectImageOutput>
 */
final readonly class UploadSolidarityProjectImageProcessor implements ProcessorInterface
{
    public function __construct(
        private UploadSolidarityProjectImage $useCase,
        private RequestStack $requestStack,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AdminSolidarityProjectImageOutput
    {
        // Platform curation action reserved for ROLE_ADMIN (enforced by the
        // operation's security expression). Resolving the current user adds a
        // defense-in-depth 401 if the endpoint is ever reached anonymously.
        $this->currentUser->id();

        $request = $this->requestStack->getCurrentRequest();
        $file = $request?->files->get('file');

        if (!$file instanceof UploadedFile) {
            throw new \InvalidArgumentException('No file uploaded.');
        }

        $filename = $this->useCase->handle(new UploadSolidarityProjectImageCommand(
            content: $file->getContent(),
            // Real MIME sniffed from the bytes (finfo), not the client-supplied
            // Content-Type header which an attacker fully controls.
            mimeType: $file->getMimeType() ?? '',
            size: $file->getSize() ?? 0,
        ));

        $output = new AdminSolidarityProjectImageOutput();
        $output->imageUrl = $request->getSchemeAndHttpHost().'/uploads/solidarity-projects/'.$filename;

        return $output;
    }
}
