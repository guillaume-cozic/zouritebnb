<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\Messenger;

use App\Team\Domain\Event\CoHostInvited;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class LogCoHostInvitedListener
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(CoHostInvited $event): void
    {
        $this->logger->info(\sprintf(
            'Co-host invitation sent to %s for team %s (invitationId: %s)',
            $event->email,
            $event->teamId->toRfc4122(),
            $event->invitationId->toRfc4122(),
        ));
    }
}
