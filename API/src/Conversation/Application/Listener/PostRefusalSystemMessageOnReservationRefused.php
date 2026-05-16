<?php

declare(strict_types=1);

namespace App\Conversation\Application\Listener;

use App\Conversation\Application\UseCase\PostSystemMessage;
use App\Conversation\Domain\Command\PostSystemMessageCommand;
use App\Shared\Domain\Event\ReservationRefused;

final readonly class PostRefusalSystemMessageOnReservationRefused
{
    public function __construct(private PostSystemMessage $postSystemMessage)
    {
    }

    public function __invoke(ReservationRefused $event): void
    {
        $body = $event->isAutomatic
            ? "La demande de réservation a été automatiquement refusée car l'hôte n'a pas répondu sous 24h."
            : "L'hôte a refusé cette demande de réservation.";

        $this->postSystemMessage->handle(new PostSystemMessageCommand(
            reservationId: $event->reservationId,
            body: $body,
        ));
    }
}
