<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

enum UserTokenPurpose: string
{
    case PasswordReset = 'password_reset';
    case EmailVerification = 'email_verification';
}
