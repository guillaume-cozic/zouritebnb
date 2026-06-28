<?php

declare(strict_types=1);

namespace App\Conversation\Application\Listener;

use App\Conversation\Application\UseCase\PostSystemMessage;
use App\Conversation\Domain\Command\PostSystemMessageCommand;
use App\Shared\Domain\Event\ReservationModificationRequested;

final readonly class PostMessageOnReservationModificationRequested
{
    public function __construct(private PostSystemMessage $postSystemMessage)
    {
    }

    public function __invoke(ReservationModificationRequested $event): void
    {
        $this->postSystemMessage->handle(new PostSystemMessageCommand(
            reservationId: $event->reservationId,
            body: 'Le voyageur a demandé une modification des dates de la réservation. L\'hôte doit l\'accepter ou la refuser.',
        ));
    }
}
