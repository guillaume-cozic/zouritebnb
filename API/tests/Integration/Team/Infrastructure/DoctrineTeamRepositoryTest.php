<?php

declare(strict_types=1);

namespace App\Tests\Integration\Team\Infrastructure;

use App\Team\Domain\Entity\BankAccount;
use App\Team\Domain\Entity\Bic;
use App\Team\Domain\Entity\Iban;
use App\Team\Domain\Entity\Team;
use App\Team\Domain\Port\TeamRepository;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineTeamRepositoryTest extends RepositoryTestCase
{
    private TeamRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(TeamRepository::class);
    }

    public function test_should_save_and_find_by_id(): void
    {
        $id = Uuid::v4();
        $projectId = Uuid::v4();
        $team = new Team(
            id: $id,
            favoriteSolidarityProjectId: $projectId,
        );

        $this->repository->save($team);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertEquals($projectId, $found->getFavoriteSolidarityProjectId());
        self::assertNull($found->getBankAccount());
    }

    public function test_should_return_null_when_not_found(): void
    {
        $result = $this->repository->findById(Uuid::v4());

        self::assertNull($result);
    }

    public function test_should_save_and_find_team_without_favorite_solidarity_project(): void
    {
        $id = Uuid::v4();
        $team = new Team(id: $id);

        $this->repository->save($team);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertNull($found->getFavoriteSolidarityProjectId());
        self::assertNull($found->getBankAccount());
    }

    public function test_should_save_and_find_team_with_full_bank_account(): void
    {
        $id = Uuid::v4();
        $bankAccount = new BankAccount(
            iban: new Iban('FR1420041010050500013M02606'),
            bic: new Bic('BNPAFRPP'),
            holderName: 'Solidarity Team',
        );
        $team = new Team(
            id: $id,
            bankAccount: $bankAccount,
        );

        $this->repository->save($team);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertNotNull($found->getBankAccount());
        self::assertSame('FR1420041010050500013M02606', $found->getBankAccount()->iban->value());
        self::assertNotNull($found->getBankAccount()->bic);
        self::assertSame('BNPAFRPP', $found->getBankAccount()->bic->value());
        self::assertSame('Solidarity Team', $found->getBankAccount()->holderName);
    }

    public function test_should_save_and_find_team_with_bank_account_without_bic(): void
    {
        $id = Uuid::v4();
        $bankAccount = new BankAccount(
            iban: new Iban('FR1420041010050500013M02606'),
            bic: null,
            holderName: 'No Bic Holder',
        );
        $team = new Team(
            id: $id,
            bankAccount: $bankAccount,
        );

        $this->repository->save($team);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertNotNull($found->getBankAccount());
        self::assertSame('FR1420041010050500013M02606', $found->getBankAccount()->iban->value());
        self::assertNull($found->getBankAccount()->bic);
        self::assertSame('No Bic Holder', $found->getBankAccount()->holderName);
    }

    public function test_should_update_existing_team(): void
    {
        $id = Uuid::v4();
        $team = new Team(
            id: $id,
            favoriteSolidarityProjectId: Uuid::v4(),
            bankAccount: new BankAccount(
                iban: new Iban('FR1420041010050500013M02606'),
                bic: new Bic('BNPAFRPP'),
                holderName: 'Old Holder',
            ),
        );
        $this->repository->save($team);

        $newProjectId = Uuid::v4();
        $updated = new Team(
            id: $id,
            favoriteSolidarityProjectId: $newProjectId,
            bankAccount: new BankAccount(
                iban: new Iban('DE89370400440532013000'),
                bic: new Bic('COBADEFF'),
                holderName: 'New Holder',
            ),
        );
        $this->repository->save($updated);

        $found = $this->repository->findById($id);
        self::assertNotNull($found);
        self::assertEquals($newProjectId, $found->getFavoriteSolidarityProjectId());
        self::assertNotNull($found->getBankAccount());
        self::assertSame('DE89370400440532013000', $found->getBankAccount()->iban->value());
        self::assertNotNull($found->getBankAccount()->bic);
        self::assertSame('COBADEFF', $found->getBankAccount()->bic->value());
        self::assertSame('New Holder', $found->getBankAccount()->holderName);
    }

    public function test_should_remove_bank_account_when_updating_with_null(): void
    {
        $id = Uuid::v4();
        $team = new Team(
            id: $id,
            bankAccount: new BankAccount(
                iban: new Iban('FR1420041010050500013M02606'),
                bic: new Bic('BNPAFRPP'),
                holderName: 'Holder',
            ),
        );
        $this->repository->save($team);

        $updated = new Team(id: $id, bankAccount: null);
        $this->repository->save($updated);

        $found = $this->repository->findById($id);
        self::assertNotNull($found);
        self::assertNull($found->getBankAccount());
    }
}
