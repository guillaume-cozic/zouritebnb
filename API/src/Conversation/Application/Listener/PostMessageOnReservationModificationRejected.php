<?php

declare(strict_types=1);

namespace App\Conversation\Application\Listener;

use App\Conversation\Application\UseCase\PostSystemMessage;
use App\Conversation\Domain\Command\PostSystemMessageCommand;
use App\Shared\Domain\Event\ReservationModificationRejected;

final readonly class PostMessageOnReservationModificationRejected
{
    public function __construct(private PostSystemMessage $postSystemMessage)
    {
    }

    public function __invoke(ReservationModificationRejected $event): void
    {
        $this->postSystemMessage->handle(new PostSystemMessageCommand(
            reservationId: $event->reservationId,
            body: 'La modification des dates a été refusée. La réservation conserve ses dates d\'origine.',
        ));
    }
}
