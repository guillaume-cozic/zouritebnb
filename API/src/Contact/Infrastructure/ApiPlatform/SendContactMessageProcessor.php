<?php

declare(strict_types=1);

namespace App\Contact\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Contact\Application\UseCase\SendContactMessage;
use App\Contact\Domain\Command\SendContactMessageCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;

/**
 * @implements ProcessorInterface<SendContactMessageInput, void>
 */
final readonly class SendContactMessageProcessor implements ProcessorInterface
{
    public function __construct(
        private SendContactMessage $sendContactMessage,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof SendContactMessageInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', SendContactMessageInput::class, get_debug_type($data)));
        }

        $this->handler->execute(fn () => $this->sendContactMessage->handle(new SendContactMessageCommand(
            name: $data->name,
            email: $data->email,
            subject: $data->subject,
            message: $data->message,
        )));
    }
}
