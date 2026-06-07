<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

enum VerificationStatus: string
{
    case NotStarted = 'not_started';
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
}
