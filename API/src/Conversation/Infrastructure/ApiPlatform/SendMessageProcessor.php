<?php

declare(strict_types=1);

namespace App\Conversation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Conversation\Application\UseCase\SendMessage;
use App\Conversation\Domain\Command\SendMessageCommand;
use App\Conversation\Domain\Entity\Conversation;
use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Port\ConversationRepository;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<SendMessageInput, MessageOutput>
 */
final readonly class SendMessageProcessor implements ProcessorInterface
{
    public function __construct(
        private SendMessage $sendMessage,
        private ConversationRepository $repository,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MessageOutput
    {
        \assert($data instanceof SendMessageInput);

        $conversationId = (string) $uriVariables['id'];

        /** @var string $messageId */
        $messageId = $this->handler->execute(fn () => $this->sendMessage->handle(new SendMessageCommand(
            conversationId: $conversationId,
            authorUserId: $data->authorUserId,
            body: $data->body,
        )));

        $conversation = $this->repository->ofId(new ConversationId(Uuid::fromString($conversationId)));
        \assert($conversation instanceof Conversation);

        foreach ($conversation->getMessages() as $message) {
            if ($message->getId()->toString() === $messageId) {
                return MessageOutput::fromEntity($message);
            }
        }

        throw new \RuntimeException('Message was created but could not be reloaded.');
    }
}
