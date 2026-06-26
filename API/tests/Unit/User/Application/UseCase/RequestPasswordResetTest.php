<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\UseCase;

use App\Shared\Domain\Event\PasswordResetRequested;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use App\Tests\Unit\User\Infrastructure\FakeTokenGenerator;
use App\Tests\Unit\User\Infrastructure\FixedClock;
use App\Tests\Unit\User\Infrastructure\InMemoryUserRepository;
use App\Tests\Unit\User\Infrastructure\InMemoryUserTokenRepository;
use App\User\Application\UseCase\RequestPasswordReset;
use App\User\Domain\Command\RequestPasswordResetCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserToken;
use App\User\Domain\Entity\UserTokenPurpose;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RequestPasswordResetTest extends TestCase
{
    private InMemoryUserRepository $users;
    private InMemoryUserTokenRepository $tokens;
    private InMemoryEventBus $eventBus;
    private RequestPasswordReset $useCase;
    private \DateTimeImmutable $now;

    #[Before]
    public function initUseCase(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->tokens = new InMemoryUserTokenRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->now = new \DateTimeImmutable('2026-06-26 10:00:00');
        $this->useCase = new RequestPasswordReset(
            $this->users,
            $this->tokens,
            new FakeTokenGenerator('raw-reset-token'),
            new FixedClock($this->now),
            $this->eventBus,
        );
    }

    public function test_should_issue_a_hashed_token_and_dispatch_event_for_a_known_email(): void
    {
        $userId = $this->givenUser('host@example.com');

        $this->useCase->handle(new RequestPasswordResetCommand(email: 'host@example.com'));

        $stored = $this->tokens->all();
        self::assertCount(1, $stored);
        self::assertSame(UserTokenPurpose::PasswordReset, $stored[0]->getPurpose());
        self::assertTrue($userId->equals($stored[0]->getUserId()));
        self::assertSame(UserToken::hash('raw-reset-token'), $stored[0]->getHashedToken());
        self::assertEquals($this->now->modify('+1 hour'), $stored[0]->getExpiresAt());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PasswordResetRequested::class, $events[0]);
        self::assertSame('raw-reset-token', $events[0]->token);
        self::assertTrue($userId->equals($events[0]->userId));
    }

    public function test_should_silently_ignore_an_unknown_email(): void
    {
        $this->useCase->handle(new RequestPasswordResetCommand(email: 'ghost@example.com'));

        self::assertCount(0, $this->tokens->all());
        self::assertCount(0, $this->eventBus->getDispatchedEvents());
    }

    public function test_should_supersede_a_previous_pending_token(): void
    {
        $userId = $this->givenUser('host@example.com');
        $this->tokens->save(new UserToken(
            id: Uuid::v7(),
            userId: $userId,
            purpose: UserTokenPurpose::PasswordReset,
            hashedToken: UserToken::hash('old-token'),
            expiresAt: $this->now->modify('+1 hour'),
        ));

        $this->useCase->handle(new RequestPasswordResetCommand(email: 'host@example.com'));

        $stored = $this->tokens->all();
        self::assertCount(1, $stored);
        self::assertSame(UserToken::hash('raw-reset-token'), $stored[0]->getHashedToken());
    }

    private function givenUser(string $email): Uuid
    {
        $id = Uuid::v7();
        $this->users->save(new User(
            id: $id,
            email: $email,
            hashedPassword: 'hashed:secret',
            teamId: Uuid::v7(),
        ));

        return $id;
    }
}
