<?php

declare(strict_types=1);

namespace App\Conversation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Conversation\Application\UseCase\SendAttachmentMessage;
use App\Conversation\Domain\Command\SendAttachmentMessageCommand;
use App\Conversation\Domain\Entity\Conversation;
use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Port\ConversationRepository;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<mixed, MessageOutput>
 */
final readonly class SendAttachmentMessageProcessor implements ProcessorInterface
{
    public function __construct(
        private SendAttachmentMessage $sendAttachmentMessage,
        private ConversationRepository $repository,
        private TransactionalUseCaseHandler $handler,
        private RequestStack $requestStack,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MessageOutput
    {
        $conversationId = (string) $uriVariables['id'];
        $authorUserId = $this->currentUser->id()->toRfc4122();

        $request = $this->requestStack->getCurrentRequest();
        $file = $request?->files->get('file');

        if (!$file instanceof UploadedFile) {
            throw new \InvalidArgumentException('No file uploaded.');
        }

        $body = $request->request->get('body');

        /** @var string $messageId */
        $messageId = $this->handler->execute(fn () => $this->sendAttachmentMessage->handle(new SendAttachmentMessageCommand(
            conversationId: $conversationId,
            authorUserId: $authorUserId,
            body: null !== $body ? (string) $body : null,
            content: $file->getContent(),
            // Real MIME sniffed from the bytes (finfo), not the client-supplied
            // Content-Type header which an attacker fully controls.
            mimeType: $file->getMimeType() ?? '',
            size: $file->getSize(),
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
