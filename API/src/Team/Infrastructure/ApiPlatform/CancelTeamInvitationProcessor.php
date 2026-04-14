<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\Team\Application\UseCase\CancelTeamInvitation;
use App\Team\Domain\Command\CancelTeamInvitationCommand;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<mixed, void>
 */
final readonly class CancelTeamInvitationProcessor implements ProcessorInterface
{
    public function __construct(
        private CancelTeamInvitation $useCase,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $this->handler->execute(fn () => $this->useCase->handle(new CancelTeamInvitationCommand(
            invitationId: Uuid::fromString($uriVariables['id']),
        )));
    }
}
