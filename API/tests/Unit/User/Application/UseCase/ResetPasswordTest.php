<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\UseCase;

use App\Tests\Unit\User\Infrastructure\FakePasswordHasher;
use App\Tests\Unit\User\Infrastructure\FixedClock;
use App\Tests\Unit\User\Infrastructure\InMemoryUserRepository;
use App\Tests\Unit\User\Infrastructure\InMemoryUserTokenRepository;
use App\User\Application\UseCase\ResetPassword;
use App\User\Domain\Command\ResetPasswordCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserToken;
use App\User\Domain\Entity\UserTokenPurpose;
use App\User\Domain\Exception\InvalidPasswordResetTokenException;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ResetPasswordTest extends TestCase
{
    private InMemoryUserRepository $users;
    private InMemoryUserTokenRepository $tokens;
    private ResetPassword $useCase;
    private \DateTimeImmutable $now;
    private Uuid $userId;

    #[Before]
    public function initUseCase(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->tokens = new InMemoryUserTokenRepository();
        $this->now = new \DateTimeImmutable('2026-06-26 10:00:00');
        $this->useCase = new ResetPassword(
            $this->users,
            $this->tokens,
            new FakePasswordHasher(),
            new FixedClock($this->now),
        );

        $this->userId = Uuid::v7();
        $this->users->save(new User(
            id: $this->userId,
            email: 'host@example.com',
            hashedPassword: 'hashed:old',
            teamId: Uuid::v7(),
        ));
    }

    public function test_should_change_password_and_consume_the_token(): void
    {
        $this->givenToken('raw-token', $this->now->modify('+1 hour'));

        $this->useCase->handle(new ResetPasswordCommand(token: 'raw-token', newPassword: 'brand-new'));

        self::assertSame('hashed:brand-new', $this->users->findById($this->userId)?->getHashedPassword());
        self::assertNotNull($this->tokens->findByHash(UserToken::hash('raw-token'))?->getUsedAt());
    }

    public function test_should_reject_an_unknown_token(): void
    {
        $this->expectException(InvalidPasswordResetTokenException::class);

        $this->useCase->handle(new ResetPasswordCommand(token: 'nope', newPassword: 'brand-new'));
    }

    public function test_should_reject_an_expired_token(): void
    {
        $this->givenToken('raw-token', $this->now->modify('-1 second'));

        $this->expectException(InvalidPasswordResetTokenException::class);

        try {
            $this->useCase->handle(new ResetPasswordCommand(token: 'raw-token', newPassword: 'brand-new'));
        } finally {
            self::assertSame('hashed:old', $this->users->findById($this->userId)?->getHashedPassword());
        }
    }

    public function test_should_reject_an_already_used_token(): void
    {
        $token = $this->givenToken('raw-token', $this->now->modify('+1 hour'));
        $token->markUsed($this->now->modify('-5 minutes'));

        $this->expectException(InvalidPasswordResetTokenException::class);

        $this->useCase->handle(new ResetPasswordCommand(token: 'raw-token', newPassword: 'brand-new'));
    }

    public function test_should_reject_a_token_of_a_different_purpose(): void
    {
        $this->givenToken('raw-token', $this->now->modify('+1 hour'), UserTokenPurpose::EmailVerification);

        $this->expectException(InvalidPasswordResetTokenException::class);

        $this->useCase->handle(new ResetPasswordCommand(token: 'raw-token', newPassword: 'brand-new'));
    }

    private function givenToken(string $raw, \DateTimeImmutable $expiresAt, UserTokenPurpose $purpose = UserTokenPurpose::PasswordReset): UserToken
    {
        $token = new UserToken(
            id: Uuid::v7(),
            userId: $this->userId,
            purpose: $purpose,
            hashedToken: UserToken::hash($raw),
            expiresAt: $expiresAt,
        );
        $this->tokens->save($token);

        return $token;
    }
}
