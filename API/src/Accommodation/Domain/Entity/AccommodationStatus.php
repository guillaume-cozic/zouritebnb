<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Entity;

enum AccommodationStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
