<?php

declare(strict_types=1);

namespace App\User\Domain\Port;

use App\User\Domain\Entity\UserToken;
use App\User\Domain\Entity\UserTokenPurpose;
use Symfony\Component\Uid\Uuid;

interface UserTokenRepository
{
    public function save(UserToken $token): void;

    public function findByHash(string $hashedToken): ?UserToken;

    /**
     * Invalidates every still-usable token of the given purpose for a user, so that
     * issuing a fresh token (e.g. a new reset link) supersedes any previous one.
     */
    public function deleteUsableFor(Uuid $userId, UserTokenPurpose $purpose): void;
}
