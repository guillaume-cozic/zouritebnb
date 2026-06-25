<?php

declare(strict_types=1);

namespace App\Conversation\Application\Listener;

use App\Conversation\Application\UseCase\PostSystemMessage;
use App\Conversation\Domain\Command\PostSystemMessageCommand;
use App\Shared\Domain\Event\ReservationConfirmed;

final readonly class PostConfirmationSystemMessageOnReservationConfirmed
{
    public function __construct(private PostSystemMessage $postSystemMessage)
    {
    }

    public function __invoke(ReservationConfirmed $event): void
    {
        $this->postSystemMessage->handle(new PostSystemMessageCommand(
            reservationId: $event->reservationId,
            body: "L'hôte a accepté votre réservation. Bon séjour !",
        ));
    }
}
