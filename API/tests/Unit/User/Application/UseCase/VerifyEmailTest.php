<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\UseCase;

use App\Tests\Unit\User\Infrastructure\FixedClock;
use App\Tests\Unit\User\Infrastructure\InMemoryUserRepository;
use App\Tests\Unit\User\Infrastructure\InMemoryUserTokenRepository;
use App\User\Application\UseCase\VerifyEmail;
use App\User\Domain\Command\VerifyEmailCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserToken;
use App\User\Domain\Entity\UserTokenPurpose;
use App\User\Domain\Exception\InvalidEmailVerificationTokenException;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class VerifyEmailTest extends TestCase
{
    private InMemoryUserRepository $users;
    private InMemoryUserTokenRepository $tokens;
    private VerifyEmail $useCase;
    private \DateTimeImmutable $now;
    private Uuid $userId;

    #[Before]
    public function initUseCase(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->tokens = new InMemoryUserTokenRepository();
        $this->now = new \DateTimeImmutable('2026-06-26 10:00:00');
        $this->useCase = new VerifyEmail($this->users, $this->tokens, new FixedClock($this->now));

        $this->userId = Uuid::v7();
        $this->users->save(new User(
            id: $this->userId,
            email: 'host@example.com',
            hashedPassword: 'hashed:secret',
            teamId: Uuid::v7(),
        ));
    }

    public function test_should_mark_email_verified_and_consume_the_token(): void
    {
        $this->givenToken('raw-token', $this->now->modify('+24 hours'));

        $this->useCase->handle(new VerifyEmailCommand(token: 'raw-token'));

        $user = $this->users->findById($this->userId);
        self::assertTrue($user?->isEmailVerified());
        self::assertEquals($this->now, $user?->getEmailVerifiedAt());
        self::assertNotNull($this->tokens->findByHash(UserToken::hash('raw-token'))?->getUsedAt());
    }

    public function test_should_reject_an_unknown_token(): void
    {
        $this->expectException(InvalidEmailVerificationTokenException::class);

        $this->useCase->handle(new VerifyEmailCommand(token: 'nope'));
    }

    public function test_should_reject_an_expired_token(): void
    {
        $this->givenToken('raw-token', $this->now->modify('-1 second'));

        $this->expectException(InvalidEmailVerificationTokenException::class);

        try {
            $this->useCase->handle(new VerifyEmailCommand(token: 'raw-token'));
        } finally {
            self::assertFalse($this->users->findById($this->userId)?->isEmailVerified());
        }
    }

    public function test_should_reject_a_token_of_a_different_purpose(): void
    {
        $this->givenToken('raw-token', $this->now->modify('+24 hours'), UserTokenPurpose::PasswordReset);

        $this->expectException(InvalidEmailVerificationTokenException::class);

        $this->useCase->handle(new VerifyEmailCommand(token: 'raw-token'));
    }

    private function givenToken(string $raw, \DateTimeImmutable $expiresAt, UserTokenPurpose $purpose = UserTokenPurpose::EmailVerification): void
    {
        $this->tokens->save(new UserToken(
            id: Uuid::v7(),
            userId: $this->userId,
            purpose: $purpose,
            hashedToken: UserToken::hash($raw),
            expiresAt: $expiresAt,
        ));
    }
}
