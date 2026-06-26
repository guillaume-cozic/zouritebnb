<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use Symfony\Component\Uid\Uuid;

/**
 * A single-use, time-limited secret tied to a user — used both for password resets
 * and email verification (told apart by {@see UserTokenPurpose}).
 *
 * Only the SHA-256 hash of the raw token is ever stored: the raw value lives only in
 * the email link, so a database leak cannot be replayed to reset passwords or verify
 * emails. Lookups hash the incoming token with {@see UserToken::hash()} and compare.
 */
final class UserToken
{
    public function __construct(
        private readonly Uuid $id,
        private readonly Uuid $userId,
        private readonly UserTokenPurpose $purpose,
        private readonly string $hashedToken,
        private readonly \DateTimeImmutable $expiresAt,
        private ?\DateTimeImmutable $usedAt = null,
    ) {
    }

    /**
     * Hashes a raw token for storage and lookup. Deterministic and pure, so it lives
     * in the domain: there is no secret key, the hash only avoids storing the plaintext.
     */
    public static function hash(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getPurpose(): UserTokenPurpose
    {
        return $this->purpose;
    }

    public function getHashedToken(): string
    {
        return $this->hashedToken;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getUsedAt(): ?\DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function isUsable(UserTokenPurpose $purpose, \DateTimeImmutable $now): bool
    {
        return $this->purpose === $purpose
            && null === $this->usedAt
            && $now < $this->expiresAt;
    }

    public function markUsed(\DateTimeImmutable $usedAt): void
    {
        $this->usedAt = $usedAt;
    }
}
