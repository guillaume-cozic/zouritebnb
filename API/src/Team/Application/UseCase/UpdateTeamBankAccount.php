<?php

declare(strict_types=1);

namespace App\Team\Application\UseCase;

use App\Shared\Domain\Port\EventBus;
use App\Team\Domain\Command\UpdateTeamBankAccountCommand;
use App\Team\Domain\Entity\BankAccount;
use App\Team\Domain\Entity\Bic;
use App\Team\Domain\Entity\Iban;
use App\Team\Domain\Exception\TeamNotFoundException;
use App\Team\Domain\Port\TeamRepository;

final readonly class UpdateTeamBankAccount
{
    public function __construct(
        private TeamRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(UpdateTeamBankAccountCommand $command): void
    {
        $team = $this->repository->findById($command->teamId);

        if (null === $team) {
            throw TeamNotFoundException::becauseNotFound($command->teamId->toRfc4122());
        }

        $bankAccount = null;
        if (null !== $command->iban && '' !== trim($command->iban)) {
            $bankAccount = new BankAccount(
                iban: new Iban($command->iban),
                bic: (null !== $command->bic && '' !== trim($command->bic)) ? new Bic($command->bic) : null,
                holderName: $command->holderName ?? '',
            );
        }

        $team->updateBankAccount($bankAccount);
        $this->repository->save($team);

        $this->eventBus->dispatch($team->releaseEvents());
    }
}
