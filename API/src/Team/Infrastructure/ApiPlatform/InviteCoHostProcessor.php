<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\Team\Application\UseCase\InviteCoHost;
use App\Team\Domain\Command\InviteCoHostCommand;
use App\Team\Domain\Exception\InvalidInvitationException;
use App\Team\Domain\Port\TeamInvitationRepository;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<InviteCoHostInput, TeamInvitationOutput>
 */
final readonly class InviteCoHostProcessor implements ProcessorInterface
{
    public function __construct(
        private InviteCoHost $useCase,
        private TeamInvitationRepository $repository,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TeamInvitationOutput
    {
        \assert($data instanceof InviteCoHostInput);

        if (null === $data->email || '' === trim($data->email)) {
            throw InvalidInvitationException::becauseEmptyEmail();
        }

        /** @var string $id */
        $id = $this->handler->execute(fn () => $this->useCase->handle(new InviteCoHostCommand(
            teamId: Uuid::fromString($uriVariables['id']),
            email: $data->email,
        )));

        $invitation = $this->repository->findById(Uuid::fromString($id));
        \assert(null !== $invitation);

        return TeamInvitationOutput::fromDomain($invitation);
    }
}
