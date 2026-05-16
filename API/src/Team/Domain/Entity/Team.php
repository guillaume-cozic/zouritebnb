<?php

declare(strict_types=1);

namespace App\Team\Domain\Entity;

use App\Shared\Domain\Entity\AggregateRoot;
use App\Team\Domain\Event\TeamBankAccountUpdated;
use App\Team\Domain\Event\TeamCreated;
use App\Team\Domain\Event\TeamFavoriteSolidarityProjectUpdated;
use Symfony\Component\Uid\Uuid;

final class Team extends AggregateRoot
{
    public function __construct(
        private readonly Uuid $id,
        private ?Uuid $favoriteSolidarityProjectId = null,
        private ?BankAccount $bankAccount = null,
    ) {
    }

    public static function create(Uuid $id): self
    {
        $team = new self(id: $id);
        $team->recordEvent(new TeamCreated($id));

        return $team;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getFavoriteSolidarityProjectId(): ?Uuid
    {
        return $this->favoriteSolidarityProjectId;
    }

    public function getBankAccount(): ?BankAccount
    {
        return $this->bankAccount;
    }

    public function updateFavoriteSolidarityProject(?Uuid $projectId): void
    {
        $this->favoriteSolidarityProjectId = $projectId;
        $this->recordEvent(new TeamFavoriteSolidarityProjectUpdated($this->id));
    }

    public function updateBankAccount(?BankAccount $bankAccount): void
    {
        $this->bankAccount = $bankAccount;
        $this->recordEvent(new TeamBankAccountUpdated($this->id));
    }
}
