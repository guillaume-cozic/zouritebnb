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

        // Miniature pas (encore) générée : retomber sur l'original, dont on
        // retrouve le nom en retirant le suffixe conventionnel `-thumb.webp`
        // (convention partagée avec le module Accommodation, non importable
        // ici — Shared ne dépend d'aucun module).
        if (!is_file($path) && 1 === preg_match('/^(.+)-thumb\.webp$/', $filename, $matches)) {
            foreach (['webp', 'jpg', 'jpeg', 'png'] as $extension) {
                $originalPath = \sprintf('%s/%s.%s', $this->photoUploadDir, $matches[1], $extension);
                if (is_file($originalPath)) {
                    $path = $originalPath;
                    break;
                }
            }
        }

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
