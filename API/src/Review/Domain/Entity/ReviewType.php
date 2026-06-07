<?php

declare(strict_types=1);

namespace App\Review\Domain\Entity;

enum ReviewType: string
{
    /** Guest reviews the accommodation. */
    case Accommodation = 'accommodation';

    /** Host team reviews the guest. */
    case Guest = 'guest';
}
