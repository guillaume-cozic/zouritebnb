<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Entity\TransformedImage;
use PHPUnit\Framework\TestCase;

final class TransformedImageTest extends TestCase
{
    public function test_should_expose_content_mime_type_and_size(): void
    {
        $image = new TransformedImage(
            content: 'binary-content',
            mimeType: 'image/webp',
            size: 2048,
        );

        self::assertSame('binary-content', $image->content());
        self::assertSame('image/webp', $image->mimeType());
        self::assertSame(2048, $image->size());
    }
}
