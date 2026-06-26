<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure;

use App\User\Domain\Entity\UserToken;
use App\User\Domain\Entity\UserTokenPurpose;
use App\User\Domain\Port\UserTokenRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryUserTokenRepository implements UserTokenRepository
{
    /** @var array<string, UserToken> */
    private array $tokens = [];

    public function save(UserToken $token): void
    {
        $this->tokens[$token->getId()->toRfc4122()] = $token;
    }

    public function findByHash(string $hashedToken): ?UserToken
    {
        foreach ($this->tokens as $token) {
            if ($token->getHashedToken() === $hashedToken) {
                return $token;
            }
        }

        return null;
    }

    public function deleteUsableFor(Uuid $userId, UserTokenPurpose $purpose): void
    {
        foreach ($this->tokens as $key => $token) {
            if ($token->getUserId()->equals($userId)
                && $token->getPurpose() === $purpose
                && null === $token->getUsedAt()
            ) {
                unset($this->tokens[$key]);
            }
        }
    }

    /** @return UserToken[] */
    public function all(): array
    {
        return array_values($this->tokens);
    }
}
