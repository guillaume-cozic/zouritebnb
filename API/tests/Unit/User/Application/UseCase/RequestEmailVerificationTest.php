<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\UseCase;

use App\Shared\Domain\Event\EmailVerificationRequested;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use App\Tests\Unit\User\Infrastructure\FakeTokenGenerator;
use App\Tests\Unit\User\Infrastructure\FixedClock;
use App\Tests\Unit\User\Infrastructure\InMemoryUserRepository;
use App\Tests\Unit\User\Infrastructure\InMemoryUserTokenRepository;
use App\User\Application\UseCase\RequestEmailVerification;
use App\User\Domain\Command\RequestEmailVerificationCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserToken;
use App\User\Domain\Entity\UserTokenPurpose;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RequestEmailVerificationTest extends TestCase
{
    private InMemoryUserRepository $users;
    private InMemoryUserTokenRepository $tokens;
    private InMemoryEventBus $eventBus;
    private RequestEmailVerification $useCase;
    private \DateTimeImmutable $now;

    #[Before]
    public function initUseCase(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->tokens = new InMemoryUserTokenRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->now = new \DateTimeImmutable('2026-06-26 10:00:00');
        $this->useCase = new RequestEmailVerification(
            $this->users,
            $this->tokens,
            new FakeTokenGenerator('raw-verify-token'),
            new FixedClock($this->now),
            $this->eventBus,
        );
    }

    public function test_should_issue_a_token_and_dispatch_event_for_an_unverified_user(): void
    {
        $userId = $this->givenUser(emailVerified: false);

        $this->useCase->handle(new RequestEmailVerificationCommand($userId));

        $stored = $this->tokens->all();
        self::assertCount(1, $stored);
        self::assertSame(UserTokenPurpose::EmailVerification, $stored[0]->getPurpose());
        self::assertSame(UserToken::hash('raw-verify-token'), $stored[0]->getHashedToken());
        self::assertEquals($this->now->modify('+24 hours'), $stored[0]->getExpiresAt());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(EmailVerificationRequested::class, $events[0]);
        self::assertSame('raw-verify-token', $events[0]->token);
    }

    public function test_should_do_nothing_when_email_is_already_verified(): void
    {
        $userId = $this->givenUser(emailVerified: true);

        $this->useCase->handle(new RequestEmailVerificationCommand($userId));

        self::assertCount(0, $this->tokens->all());
        self::assertCount(0, $this->eventBus->getDispatchedEvents());
    }

    public function test_should_do_nothing_for_an_unknown_user(): void
    {
        $this->useCase->handle(new RequestEmailVerificationCommand(Uuid::v7()));

        self::assertCount(0, $this->tokens->all());
        self::assertCount(0, $this->eventBus->getDispatchedEvents());
    }

    private function givenUser(bool $emailVerified): Uuid
    {
        $id = Uuid::v7();
        $this->users->save(new User(
            id: $id,
            email: 'host@example.com',
            hashedPassword: 'hashed:secret',
            teamId: Uuid::v7(),
            emailVerified: $emailVerified,
            emailVerifiedAt: $emailVerified ? $this->now : null,
        ));

        return $id;
    }
}
