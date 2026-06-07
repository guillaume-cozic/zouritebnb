<?php

declare(strict_types=1);

namespace App\Tests\Unit\Team\Application\UseCase;

use App\Shared\Domain\Port\UuidGenerator;
use App\Team\Application\UseCase\UpdateTeamBankAccount;
use App\Team\Domain\Command\UpdateTeamBankAccountCommand;
use App\Team\Domain\Entity\Team;
use App\Team\Domain\Event\TeamBankAccountUpdated;
use App\Team\Domain\Exception\InvalidBankAccountException;
use App\Team\Domain\Exception\TeamNotFoundException;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use App\Tests\Unit\Team\Infrastructure\InMemoryTeamRepository;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateTeamBankAccountTest extends TestCase
{
    private const string TEAM_ID = '01961e2f-dead-7000-beef-0000000000b1';

    private InMemoryTeamRepository $repository;
    private InMemoryEventBus $eventBus;
    private UpdateTeamBankAccount $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryTeamRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateTeamBankAccount($this->repository, $this->eventBus);
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_set_bank_account_with_iban_bic_and_holder_name(): void
    {
        $teamId = $this->givenTeam();

        $this->useCase->handle(new UpdateTeamBankAccountCommand(
            teamId: $teamId,
            iban: 'FR76 3000 6000 0112 3456 7890 189',
            bic: 'BNPAFRPP',
            holderName: 'Alice Martin',
        ));

        $bankAccount = $this->repository->findById($teamId)?->getBankAccount();
        self::assertNotNull($bankAccount);
        self::assertSame('FR7630006000011234567890189', $bankAccount->iban->value());
        self::assertNotNull($bankAccount->bic);
        self::assertSame('BNPAFRPP', $bankAccount->bic->value());
        self::assertSame('Alice Martin', $bankAccount->holderName);

        $this->assertBankAccountUpdatedDispatched($teamId);
    }

    public function test_should_set_bank_account_without_bic(): void
    {
        $teamId = $this->givenTeam();

        $this->useCase->handle(new UpdateTeamBankAccountCommand(
            teamId: $teamId,
            iban: 'FR7630006000011234567890189',
            bic: null,
            holderName: 'Alice Martin',
        ));

        $bankAccount = $this->repository->findById($teamId)?->getBankAccount();
        self::assertNotNull($bankAccount);
        self::assertNull($bankAccount->bic);

        $this->assertBankAccountUpdatedDispatched($teamId);
    }

    #[DataProvider('blankBicProvider')]
    public function test_should_set_bank_account_when_bic_is_blank(?string $bic): void
    {
        $teamId = $this->givenTeam();

        $this->useCase->handle(new UpdateTeamBankAccountCommand(
            teamId: $teamId,
            iban: 'FR7630006000011234567890189',
            bic: $bic,
            holderName: 'Alice Martin',
        ));

        $bankAccount = $this->repository->findById($teamId)?->getBankAccount();
        self::assertNotNull($bankAccount);
        self::assertNull($bankAccount->bic);
    }

    public static function blankBicProvider(): \Generator
    {
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
    }

    #[DataProvider('blankIbanProvider')]
    public function test_should_clear_bank_account_when_iban_is_blank(?string $iban): void
    {
        $teamId = $this->givenTeam();
        // First set a bank account.
        $this->useCase->handle(new UpdateTeamBankAccountCommand(
            teamId: $teamId,
            iban: 'FR7630006000011234567890189',
            bic: 'BNPAFRPP',
            holderName: 'Alice Martin',
        ));
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateTeamBankAccount($this->repository, $this->eventBus);

        $this->useCase->handle(new UpdateTeamBankAccountCommand(
            teamId: $teamId,
            iban: $iban,
            bic: 'BNPAFRPP',
            holderName: 'Alice Martin',
        ));

        self::assertNull($this->repository->findById($teamId)?->getBankAccount());
        $this->assertBankAccountUpdatedDispatched($teamId);
    }

    public static function blankIbanProvider(): \Generator
    {
        yield 'null iban' => [null];
        yield 'empty iban' => [''];
        yield 'whitespace iban' => ['   '];
    }

    public function test_should_default_holder_name_to_empty_and_reject_it(): void
    {
        // When iban is provided but holderName is null, BankAccount receives '' and throws.
        $teamId = $this->givenTeam();

        $this->expectException(InvalidBankAccountException::class);
        $this->expectExceptionMessage('Account holder name must not be empty.');

        $this->useCase->handle(new UpdateTeamBankAccountCommand(
            teamId: $teamId,
            iban: 'FR7630006000011234567890189',
            bic: null,
            holderName: null,
        ));
    }

    public function test_should_reject_invalid_iban(): void
    {
        $teamId = $this->givenTeam();

        $this->expectException(InvalidBankAccountException::class);

        $this->useCase->handle(new UpdateTeamBankAccountCommand(
            teamId: $teamId,
            iban: 'INVALID-IBAN',
            bic: null,
            holderName: 'Alice Martin',
        ));
    }

    public function test_should_throw_when_team_not_found(): void
    {
        $missingId = Uuid::fromString(self::TEAM_ID);

        $this->expectException(TeamNotFoundException::class);
        $this->expectExceptionMessage(\sprintf('Team "%s" not found.', $missingId->toRfc4122()));

        $this->useCase->handle(new UpdateTeamBankAccountCommand(
            teamId: $missingId,
            iban: 'FR7630006000011234567890189',
            bic: 'BNPAFRPP',
            holderName: 'Alice Martin',
        ));
    }

    private function givenTeam(): Uuid
    {
        $teamId = Uuid::fromString(self::TEAM_ID);
        $team = new Team($teamId);
        $this->repository->save($team);

        return $teamId;
    }

    private function assertBankAccountUpdatedDispatched(Uuid $teamId): void
    {
        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(TeamBankAccountUpdated::class, $events[0]);
        self::assertTrue($teamId->equals($events[0]->teamId));
    }
}
