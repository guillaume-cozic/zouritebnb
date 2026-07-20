<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class ServePhotoController
{
    public function __construct(
        private string $photoUploadDir,
    ) {
    }

    #[Route('/uploads/photos/{filename}', name: 'serve_photo', methods: ['GET'])]
    public function __invoke(string $filename): Response
    {
        $path = $this->photoUploadDir.'/'.$filename;

        if (!is_file($path)) {
            return new Response('Not found', Response::HTTP_NOT_FOUND);
        }

        // Filenames are immutable UUIDs (a new upload gets a new URL), so
        // browsers and the CDN edge can cache the file indefinitely.
        $response = new BinaryFileResponse($path);
        $response->setPublic();
        $response->setMaxAge(31536000);
        $response->setImmutable();

        return $response;
    }
}
