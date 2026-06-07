<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

enum IdentityDocumentType: string
{
    case Passport = 'passport';
    case IdCard = 'id_card';
    case DrivingLicense = 'driving_license';
}
