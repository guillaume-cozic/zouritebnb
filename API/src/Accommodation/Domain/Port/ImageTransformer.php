<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Port;

use App\Accommodation\Domain\Entity\TransformedImage;

interface ImageTransformer
{
    public function transform(string $content, string $mimeType): TransformedImage;

    /**
     * Variante réduite (largeur plafonnée) destinée aux listings : quelques
     * dizaines de Ko au lieu de l'original plein format.
     */
    public function thumbnail(string $content, string $mimeType): TransformedImage;
}
