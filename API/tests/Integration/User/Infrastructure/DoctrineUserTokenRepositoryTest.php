<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Infrastructure;

use App\Tests\Integration\RepositoryTestCase;
use App\User\Domain\Entity\UserToken;
use App\User\Domain\Entity\UserTokenPurpose;
use App\User\Domain\Port\UserTokenRepository;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineUserTokenRepositoryTest extends RepositoryTestCase
{
    private UserTokenRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(UserTokenRepository::class);
    }

    public function test_should_save_and_find_by_hash(): void
    {
        $userId = Uuid::v4();
        $token = new UserToken(
            id: Uuid::v4(),
            userId: $userId,
            purpose: UserTokenPurpose::PasswordReset,
            hashedToken: UserToken::hash('raw-token'),
            expiresAt: new \DateTimeImmutable('2026-06-26 11:00:00'),
        );

        $this->repository->save($token);
        $found = $this->repository->findByHash(UserToken::hash('raw-token'));

        self::assertNotNull($found);
        self::assertEquals($token->getId(), $found->getId());
        self::assertEquals($userId, $found->getUserId());
        self::assertSame(UserTokenPurpose::PasswordReset, $found->getPurpose());
        self::assertEquals(new \DateTimeImmutable('2026-06-26 11:00:00'), $found->getExpiresAt());
        self::assertNull($found->getUsedAt());
    }

    public function test_should_return_null_when_hash_not_found(): void
    {
        self::assertNull($this->repository->findByHash(UserToken::hash('missing')));
    }

    public function test_should_persist_used_at_when_token_is_consumed(): void
    {
        $token = new UserToken(
            id: Uuid::v4(),
            userId: Uuid::v4(),
            purpose: UserTokenPurpose::EmailVerification,
            hashedToken: UserToken::hash('raw-token'),
            expiresAt: new \DateTimeImmutable('2026-06-27 10:00:00'),
        );
        $this->repository->save($token);

        $usedAt = new \DateTimeImmutable('2026-06-26 12:00:00');
        $token->markUsed($usedAt);
        $this->repository->save($token);

        $found = $this->repository->findByHash(UserToken::hash('raw-token'));
        self::assertNotNull($found);
        self::assertEquals($usedAt, $found->getUsedAt());
    }

    public function test_should_delete_only_usable_tokens_of_the_given_purpose(): void
    {
        $userId = Uuid::v4();
        $expiresAt = new \DateTimeImmutable('2026-06-27 10:00:00');

        $pendingReset = new UserToken(Uuid::v4(), $userId, UserTokenPurpose::PasswordReset, UserToken::hash('pending-reset'), $expiresAt);
        $usedReset = new UserToken(Uuid::v4(), $userId, UserTokenPurpose::PasswordReset, UserToken::hash('used-reset'), $expiresAt, new \DateTimeImmutable('2026-06-26 09:00:00'));
        $verification = new UserToken(Uuid::v4(), $userId, UserTokenPurpose::EmailVerification, UserToken::hash('verification'), $expiresAt);

        $this->repository->save($pendingReset);
        $this->repository->save($usedReset);
        $this->repository->save($verification);

        $this->repository->deleteUsableFor($userId, UserTokenPurpose::PasswordReset);

        // The pending reset token is gone; the used one and the other purpose remain.
        self::assertNull($this->repository->findByHash(UserToken::hash('pending-reset')));
        self::assertNotNull($this->repository->findByHash(UserToken::hash('used-reset')));
        self::assertNotNull($this->repository->findByHash(UserToken::hash('verification')));
    }
}
