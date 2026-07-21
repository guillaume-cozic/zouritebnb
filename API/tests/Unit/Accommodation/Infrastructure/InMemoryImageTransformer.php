<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Infrastructure;

use App\Accommodation\Domain\Entity\TransformedImage;
use App\Accommodation\Domain\Port\ImageTransformer;

final class InMemoryImageTransformer implements ImageTransformer
{
    public function transform(string $content, string $mimeType): TransformedImage
    {
        return new TransformedImage(
            content: $content,
            mimeType: 'image/webp',
            size: \strlen($content),
        );
    }

    public function thumbnail(string $content, string $mimeType): TransformedImage
    {
        return new TransformedImage(
            content: 'thumb:'.$content,
            mimeType: 'image/webp',
            size: \strlen('thumb:'.$content),
        );
    }
}
