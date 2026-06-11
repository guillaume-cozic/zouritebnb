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
use App\Shared\Infrastructure\Security\CurrentUser;
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
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MessageOutput
    {
        if (!$data instanceof SendMessageInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', SendMessageInput::class, get_debug_type($data)));
        }

        $conversationId = (string) $uriVariables['id'];
        $authorUserId = $this->currentUser->id()->toRfc4122();

        /** @var string $messageId */
        $messageId = $this->handler->execute(fn () => $this->sendMessage->handle(new SendMessageCommand(
            conversationId: $conversationId,
            authorUserId: $authorUserId,
            body: $data->body,
        )));

        $conversation = $this->repository->ofId(new ConversationId(Uuid::fromString($conversationId)));
        if (!$conversation instanceof Conversation) {
            throw new \RuntimeException('Conversation could not be reloaded after the operation.');
        }

        foreach ($conversation->getMessages() as $message) {
            if ($message->getId()->toString() === $messageId) {
                return MessageOutput::fromEntity($message);
            }
        }

        throw new \RuntimeException('Message was created but could not be reloaded.');
    }
}
