<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\Team\Application\UseCase\CancelTeamInvitation;
use App\Team\Domain\Command\CancelTeamInvitationCommand;
use App\Team\Domain\Port\TeamInvitationRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<mixed, void>
 */
final readonly class CancelTeamInvitationProcessor implements ProcessorInterface
{
    public function __construct(
        private CancelTeamInvitation $useCase,
        private TransactionalUseCaseHandler $handler,
        private TeamInvitationRepository $repository,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $invitationId = Uuid::fromString($uriVariables['id']);

        $invitation = $this->repository->findById($invitationId);
        if (null !== $invitation && !$invitation->getTeamId()->equals($this->currentUser->teamId())) {
            throw new AccessDeniedHttpException('You can only cancel invitations that belong to your own team.');
        }

        $this->handler->execute(fn () => $this->useCase->handle(new CancelTeamInvitationCommand(
            invitationId: $invitationId,
        )));
    }
}
