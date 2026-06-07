<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\Team\Application\UseCase\UpdateTeamBankAccount;
use App\Team\Domain\Command\UpdateTeamBankAccountCommand;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateTeamBankAccountInput, void>
 */
final readonly class UpdateTeamBankAccountProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateTeamBankAccount $useCase,
        private TransactionalUseCaseHandler $handler,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        \assert($data instanceof UpdateTeamBankAccountInput);

        $teamId = $this->currentUser->teamId();

        if (!$teamId->equals(Uuid::fromString($uriVariables['id']))) {
            throw new AccessDeniedHttpException('You can only manage the bank account of your own team.');
        }

        $this->handler->execute(fn () => $this->useCase->handle(new UpdateTeamBankAccountCommand(
            teamId: $teamId,
            iban: $data->iban,
            bic: $data->bic,
            holderName: $data->holderName,
        )));
    }
}
