<?php

declare(strict_types=1);

namespace App\Tests\Unit\SolidarityProject\Infrastructure\Gd;

use App\SolidarityProject\Domain\Exception\InvalidSolidarityProjectImageException;
use App\SolidarityProject\Infrastructure\Gd\GdSolidarityProjectImageTransformer;
use PHPUnit\Framework\TestCase;

final class GdSolidarityProjectImageTransformerTest extends TestCase
{
    public function test_should_downscale_wide_image_to_hero_width(): void
    {
        $image = imagecreatetruecolor(3200, 1600);
        ob_start();
        imagepng($image);
        $pngContent = (string) ob_get_clean();

        $transformer = new GdSolidarityProjectImageTransformer();
        $webp = $transformer->toHeroWebp($pngContent);

        self::assertStringStartsWith('RIFF', $webp);
        $result = imagecreatefromstring($webp);
        self::assertNotFalse($result);
        self::assertSame(1600, imagesx($result));
        self::assertSame(800, imagesy($result));
    }

    public function test_should_keep_small_image_size(): void
    {
        $image = imagecreatetruecolor(800, 500);
        ob_start();
        imagepng($image);
        $pngContent = (string) ob_get_clean();

        $transformer = new GdSolidarityProjectImageTransformer();
        $result = imagecreatefromstring($transformer->toHeroWebp($pngContent));

        self::assertNotFalse($result);
        self::assertSame(800, imagesx($result));
        self::assertSame(500, imagesy($result));
    }

    public function test_should_throw_when_content_is_not_a_valid_image(): void
    {
        $transformer = new GdSolidarityProjectImageTransformer();

        $this->expectException(InvalidSolidarityProjectImageException::class);

        $transformer->toHeroWebp('not-an-image');
    }
}
