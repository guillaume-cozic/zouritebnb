<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\Gd;

use App\Accommodation\Domain\Entity\TransformedImage;
use App\Accommodation\Domain\Port\ImageTransformer;

final readonly class GdImageTransformer implements ImageTransformer
{
    private const int THUMBNAIL_MAX_WIDTH = 640;

    public function transform(string $content, string $mimeType): TransformedImage
    {
        return $this->toWebp($this->createImage($content), quality: 80);
    }

    public function thumbnail(string $content, string $mimeType): TransformedImage
    {
        $image = $this->createImage($content);

        if (imagesx($image) > self::THUMBNAIL_MAX_WIDTH) {
            $scaled = imagescale($image, self::THUMBNAIL_MAX_WIDTH);

            if (false === $scaled) {
                throw new \RuntimeException('Failed to scale image.');
            }

            $image = $scaled;
        }

        return $this->toWebp($image, quality: 75);
    }

    private function createImage(string $content): \GdImage
    {
        $image = @imagecreatefromstring($content);

        if (false === $image) {
            throw new \RuntimeException('Failed to create image from content.');
        }

        return $image;
    }

    private function toWebp(\GdImage $image, int $quality): TransformedImage
    {
        ob_start();
        imagewebp($image, null, $quality);
        $webpContent = ob_get_clean();

        return new TransformedImage(
            content: $webpContent,
            mimeType: 'image/webp',
            size: \strlen($webpContent),
        );
    }
}
