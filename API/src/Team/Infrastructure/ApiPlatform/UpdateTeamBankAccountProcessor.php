<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\Team\Application\UseCase\UpdateTeamBankAccount;
use App\Team\Domain\Command\UpdateTeamBankAccountCommand;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateTeamBankAccountInput, void>
 */
final readonly class UpdateTeamBankAccountProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateTeamBankAccount $useCase,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        \assert($data instanceof UpdateTeamBankAccountInput);

        $this->handler->execute(fn () => $this->useCase->handle(new UpdateTeamBankAccountCommand(
            teamId: Uuid::fromString($uriVariables['id']),
            iban: $data->iban,
            bic: $data->bic,
            holderName: $data->holderName,
        )));
    }
}
