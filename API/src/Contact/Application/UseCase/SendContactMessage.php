<?php

declare(strict_types=1);

namespace App\Contact\Application\UseCase;

use App\Contact\Domain\Command\SendContactMessageCommand;
use App\Contact\Domain\Entity\ContactMessage;
use App\Contact\Domain\Port\ContactMessageRepository;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\EventBus;
use App\Shared\Domain\Port\UuidGenerator;

final readonly class SendContactMessage
{
    public function __construct(
        private ContactMessageRepository $repository,
        private EventBus $eventBus,
        private Clock $clock,
    ) {
    }

    public function handle(SendContactMessageCommand $command): void
    {
        $contactMessage = ContactMessage::send(
            id: UuidGenerator::generate(),
            name: $command->name,
            email: $command->email,
            subject: $command->subject,
            message: $command->message,
            sentAt: $this->clock->now(),
        );

        $this->repository->save($contactMessage);
        $this->eventBus->dispatch($contactMessage->releaseEvents());
    }
}
