<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\Gd;

use App\SolidarityProject\Domain\Exception\InvalidSolidarityProjectImageException;
use App\SolidarityProject\Domain\Port\SolidarityProjectImageTransformer;

final readonly class GdSolidarityProjectImageTransformer implements SolidarityProjectImageTransformer
{
    /** Le hero est affiché pleine largeur : 1600 px suffisent, même en retina. */
    private const int HERO_MAX_WIDTH = 1600;

    public function toHeroWebp(string $content): string
    {
        $image = @imagecreatefromstring($content);

        if (false === $image) {
            throw InvalidSolidarityProjectImageException::becauseNotAnImage();
        }

        if (imagesx($image) > self::HERO_MAX_WIDTH) {
            $scaled = imagescale($image, self::HERO_MAX_WIDTH);

            if (false === $scaled) {
                throw new \RuntimeException('Failed to scale image.');
            }

            $image = $scaled;
        }

        ob_start();
        imagewebp($image, null, 75);

        return ob_get_clean();
    }
}
