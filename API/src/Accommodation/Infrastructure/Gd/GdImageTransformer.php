<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\Gd;

use App\Accommodation\Domain\Entity\TransformedImage;
use App\Accommodation\Domain\Port\ImageTransformer;

final readonly class GdImageTransformer implements ImageTransformer
{
    public function transform(string $content, string $mimeType): TransformedImage
    {
        $image = @imagecreatefromstring($content);

        if (false === $image) {
            throw new \RuntimeException('Failed to create image from content.');
        }

        ob_start();
        imagewebp($image, null, 80);
        $webpContent = ob_get_clean();

        return new TransformedImage(
            content: $webpContent,
            mimeType: 'image/webp',
            size: \strlen($webpContent),
        );
    }
}
