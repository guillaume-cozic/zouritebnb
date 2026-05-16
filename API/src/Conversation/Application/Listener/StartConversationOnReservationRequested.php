<?php

declare(strict_types=1);

namespace App\Conversation\Application\Listener;

use App\Conversation\Application\UseCase\StartConversation;
use App\Conversation\Domain\Command\StartConversationCommand;
use App\Shared\Domain\Event\ReservationRequested;

final readonly class StartConversationOnReservationRequested
{
    public function __construct(private StartConversation $startConversation)
    {
    }

    public function __invoke(ReservationRequested $event): void
    {
        $this->startConversation->handle(new StartConversationCommand(
            reservationId: $event->reservationId,
            note: $event->note,
        ));
    }
}
