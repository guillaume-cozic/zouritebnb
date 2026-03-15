<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Port;

use App\Accommodation\Domain\Entity\TransformedImage;

interface ImageTransformer
{
    public function transform(string $content, string $mimeType): TransformedImage;
}
