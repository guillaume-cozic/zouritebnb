<?php

declare(strict_types=1);

namespace App\ActivityPoint\Domain\Entity;

enum ActivityPointCategory: string
{
    case Kitesurf = 'kitesurf';
    case Viewpoint = 'viewpoint';
    case Nature = 'nature';
    case Beach = 'beach';
    case Diving = 'diving';
    case Heritage = 'heritage';
    case Activity = 'activity';
}
