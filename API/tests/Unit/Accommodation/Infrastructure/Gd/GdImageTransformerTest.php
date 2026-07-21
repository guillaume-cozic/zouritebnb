<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Infrastructure\Gd;

use App\Accommodation\Infrastructure\Gd\GdImageTransformer;
use PHPUnit\Framework\TestCase;

final class GdImageTransformerTest extends TestCase
{
    public function test_should_transform_a_valid_image_to_webp(): void
    {
        $image = imagecreatetruecolor(2, 2);
        ob_start();
        imagepng($image);
        $pngContent = (string) ob_get_clean();

        $transformer = new GdImageTransformer();
        $result = $transformer->transform($pngContent, 'image/png');

        self::assertSame('image/webp', $result->mimeType());
        self::assertGreaterThan(0, $result->size());
        self::assertSame(\strlen($result->content()), $result->size());
        self::assertStringStartsWith('RIFF', $result->content());
    }

    public function test_should_throw_when_content_is_not_a_valid_image(): void
    {
        $transformer = new GdImageTransformer();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to create image from content.');

        $transformer->transform('not-an-image', 'image/png');
    }

    public function test_should_downscale_wide_image_to_thumbnail(): void
    {
        $image = imagecreatetruecolor(1600, 800);
        ob_start();
        imagepng($image);
        $pngContent = (string) ob_get_clean();

        $transformer = new GdImageTransformer();
        $result = $transformer->thumbnail($pngContent, 'image/png');

        self::assertSame('image/webp', $result->mimeType());
        $thumb = imagecreatefromstring($result->content());
        self::assertNotFalse($thumb);
        self::assertSame(640, imagesx($thumb));
        self::assertSame(320, imagesy($thumb));
    }

    public function test_should_keep_small_image_size_in_thumbnail(): void
    {
        $image = imagecreatetruecolor(300, 200);
        ob_start();
        imagepng($image);
        $pngContent = (string) ob_get_clean();

        $transformer = new GdImageTransformer();
        $result = $transformer->thumbnail($pngContent, 'image/png');

        $thumb = imagecreatefromstring($result->content());
        self::assertNotFalse($thumb);
        self::assertSame(300, imagesx($thumb));
        self::assertSame(200, imagesy($thumb));
    }

    public function test_should_throw_when_thumbnail_content_is_not_a_valid_image(): void
    {
        $transformer = new GdImageTransformer();

        $this->expectException(\RuntimeException::class);

        $transformer->thumbnail('not-an-image', 'image/png');
    }
}
