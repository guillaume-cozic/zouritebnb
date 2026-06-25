<?php

declare(strict_types=1);

namespace App\Conversation\Application\Listener;

use App\Conversation\Application\UseCase\PostSystemMessage;
use App\Conversation\Domain\Command\PostSystemMessageCommand;
use App\Shared\Domain\Event\ReservationCancelled;

/**
 * Records the cancellation in the linked conversation as a system message, appending
 * the optional note the canceller wrote so both parties keep a trace of it.
 */
final readonly class PostCancellationMessageOnReservationCancelled
{
    public function __construct(private PostSystemMessage $postSystemMessage)
    {
    }

    public function __invoke(ReservationCancelled $event): void
    {
        $body = 'La réservation a été annulée.';

        $note = null === $event->message ? '' : trim($event->message);
        if ('' !== $note) {
            $body .= "\n\nMessage : ".$note;
        }

        $this->postSystemMessage->handle(new PostSystemMessageCommand(
            reservationId: $event->reservationId,
            body: $body,
        ));
    }
}
