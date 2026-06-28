<?php

declare(strict_types=1);

namespace App\Conversation\Application\Listener;

use App\Conversation\Application\UseCase\PostSystemMessage;
use App\Conversation\Domain\Command\PostSystemMessageCommand;
use App\Shared\Domain\Event\ReservationModificationApproved;

final readonly class PostMessageOnReservationModificationApproved
{
    public function __construct(private PostSystemMessage $postSystemMessage)
    {
    }

    public function __invoke(ReservationModificationApproved $event): void
    {
        $this->postSystemMessage->handle(new PostSystemMessageCommand(
            reservationId: $event->reservationId,
            body: 'La modification des dates a été acceptée. Les nouvelles dates et le nouveau prix s\'appliquent désormais.',
        ));
    }
}
