<?php

declare(strict_types=1);

namespace App\Tests\E2e\User;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\E2e\AuthenticatedClientTrait;

abstract class UserApiTestCase extends ApiTestCase
{
    use AuthenticatedClientTrait;

    protected static ?bool $alwaysBootKernel = true;

    protected function insertUser(
        string $email = 'host@example.com',
        string $plainPassword = 'supersecret',
        ?string $teamId = null,
        ?string $firstName = null,
        ?string $lastName = null,
    ): string {
        return $this->createAuthUser(
            email: $email,
            plainPassword: $plainPassword,
            teamId: $teamId,
            firstName: $firstName,
            lastName: $lastName,
        );
    }
}
