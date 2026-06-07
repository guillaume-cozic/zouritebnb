<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\Team\Application\UseCase\InviteCoHost;
use App\Team\Domain\Command\InviteCoHostCommand;
use App\Team\Domain\Exception\InvalidInvitationException;
use App\Team\Domain\Port\TeamInvitationRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TeamInvitationOutput
    {
        \assert($data instanceof InviteCoHostInput);

        $teamId = $this->currentUser->teamId();

        if (!$teamId->equals(Uuid::fromString($uriVariables['id']))) {
            throw new AccessDeniedHttpException('You can only invite co-hosts to your own team.');
        }

        if (null === $data->email || '' === trim($data->email)) {
            throw InvalidInvitationException::becauseEmptyEmail();
        }

        /** @var string $id */
        $id = $this->handler->execute(fn () => $this->useCase->handle(new InviteCoHostCommand(
            teamId: $teamId,
            email: $data->email,
        )));

        $invitation = $this->repository->findById(Uuid::fromString($id));
        \assert(null !== $invitation);

        return TeamInvitationOutput::fromDomain($invitation);
    }
}
