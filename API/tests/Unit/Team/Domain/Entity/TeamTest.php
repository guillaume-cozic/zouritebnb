<?php

declare(strict_types=1);

namespace App\Tests\Unit\Team\Domain\Entity;

use App\Team\Domain\Entity\BankAccount;
use App\Team\Domain\Entity\Bic;
use App\Team\Domain\Entity\Iban;
use App\Team\Domain\Entity\Team;
use App\Team\Domain\Event\TeamBankAccountUpdated;
use App\Team\Domain\Event\TeamCreated;
use App\Team\Domain\Event\TeamFavoriteSolidarityProjectUpdated;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class TeamTest extends TestCase
{
    public function test_should_create_a_team_with_defaults(): void
    {
        $id = Uuid::v7();

        $team = new Team(id: $id);

        self::assertSame($id, $team->getId());
        self::assertNull($team->getFavoriteSolidarityProjectId());
        self::assertNull($team->getBankAccount());
        self::assertSame([], $team->releaseEvents());
    }

    public function test_should_create_a_team_with_explicit_values(): void
    {
        $id = Uuid::v7();
        $projectId = Uuid::v7();
        $bankAccount = $this->bankAccount();

        $team = new Team(
            id: $id,
            favoriteSolidarityProjectId: $projectId,
            bankAccount: $bankAccount,
        );

        self::assertSame($projectId, $team->getFavoriteSolidarityProjectId());
        self::assertSame($bankAccount, $team->getBankAccount());
    }

    public function test_should_record_team_created_event_when_created_via_factory(): void
    {
        $id = Uuid::v7();

        $team = Team::create($id);

        self::assertSame($id, $team->getId());
        $events = $team->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(TeamCreated::class, $events[0]);
        self::assertTrue($id->equals($events[0]->teamId));
    }

    public function test_should_update_favorite_solidarity_project_and_record_event(): void
    {
        $id = Uuid::v7();
        $projectId = Uuid::v7();
        $team = new Team(id: $id);

        $team->updateFavoriteSolidarityProject($projectId);

        self::assertSame($projectId, $team->getFavoriteSolidarityProjectId());
        $events = $team->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(TeamFavoriteSolidarityProjectUpdated::class, $events[0]);
        self::assertTrue($id->equals($events[0]->teamId));
    }

    public function test_should_clear_favorite_solidarity_project_with_null(): void
    {
        $team = new Team(id: Uuid::v7(), favoriteSolidarityProjectId: Uuid::v7());

        $team->updateFavoriteSolidarityProject(null);

        self::assertNull($team->getFavoriteSolidarityProjectId());
        self::assertCount(1, $team->releaseEvents());
    }

    public function test_should_update_bank_account_and_record_event(): void
    {
        $id = Uuid::v7();
        $bankAccount = $this->bankAccount();
        $team = new Team(id: $id);

        $team->updateBankAccount($bankAccount);

        self::assertSame($bankAccount, $team->getBankAccount());
        $events = $team->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(TeamBankAccountUpdated::class, $events[0]);
        self::assertTrue($id->equals($events[0]->teamId));
    }

    public function test_should_clear_bank_account_with_null(): void
    {
        $team = new Team(id: Uuid::v7(), bankAccount: $this->bankAccount());

        $team->updateBankAccount(null);

        self::assertNull($team->getBankAccount());
        self::assertCount(1, $team->releaseEvents());
    }

    private function bankAccount(): BankAccount
    {
        return new BankAccount(
            iban: new Iban('FR7630006000011234567890189'),
            bic: new Bic('BNPAFRPPXXX'),
            holderName: 'Solidarity Team',
        );
    }
}
