<?php

declare(strict_types=1);

namespace App\Conversation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Port\ConversationRepository;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<ConversationOutput>
 */
final readonly class ConversationItemProvider implements ProviderInterface
{
    public function __construct(private ConversationRepository $repository)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ConversationOutput
    {
        $id = (string) $uriVariables['id'];
        $conversation = $this->repository->ofId(new ConversationId(Uuid::fromString($id)));

        return $conversation ? ConversationOutput::fromEntity($conversation) : null;
    }
}
