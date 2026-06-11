<?php

declare(strict_types=1);

namespace App\Conversation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use App\Conversation\Domain\Entity\Conversation;
use App\Shared\ApiPlatform\State\FromEntityInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'Conversation',
    operations: [
        new Get(
            uriTemplate: '/conversations/{id}',
            openapi: new OpenApiOperation(
                summary: 'Récupérer une conversation et ses messages',
                description: 'Retourne la conversation, ses participants et l\'ensemble de ses messages (système et utilisateurs), triés par date d\'envoi. Authentification requise (401 sinon). Réservé aux participants : le loueur concerné ou un membre de l\'équipe hôte (403 sinon). 404 si introuvable.',
            ),
            normalizationContext: ['groups' => ['conversation:read']],
            security: 'is_authenticated()',
            provider: ConversationItemProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/conversations',
            openapi: new OpenApiOperation(
                summary: 'Lister mes conversations',
                description: 'Retourne les conversations de l\'utilisateur authentifié : celles où il est le loueur ou membre de l\'équipe hôte. L\'identité est déduite du token JWT (aucun filtre `userId`/`teamId` n\'est accepté). Authentification requise (401 sinon).',
            ),
            normalizationContext: ['groups' => ['conversation:read']],
            security: 'is_authenticated()',
            provider: ConversationCollectionProvider::class,
        ),
        new Post(
            uriTemplate: '/conversations/{id}/messages',
            status: 201,
            openapi: new OpenApiOperation(
                summary: 'Envoyer un message dans une conversation',
                description: 'Ajoute un nouveau message à une conversation existante. L\'auteur est l\'utilisateur authentifié, qui doit être soit le loueur, soit un membre de l\'équipe hôte. Authentification requise (401 sinon). Retourne 422 si la conversation est introuvable ou si l\'auteur n\'est pas participant.',
            ),
            denormalizationContext: ['groups' => ['conversation:write']],
            normalizationContext: ['groups' => ['conversation:read']],
            security: 'is_authenticated()',
            input: SendMessageInput::class,
            output: MessageOutput::class,
            processor: SendMessageProcessor::class,
            read: false,
        ),
    ],
)]
class ConversationOutput implements FromEntityInterface
{
    #[Groups(['conversation:read'])]
    #[ApiProperty(description: 'Identifiant unique de la conversation (UUID)')]
    public ?string $id = null;

    #[Groups(['conversation:read'])]
    #[ApiProperty(description: 'Identifiant UUID de la réservation associée')]
    public ?string $reservationId = null;

    #[Groups(['conversation:read'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'hébergement concerné')]
    public ?string $accommodationId = null;

    #[Groups(['conversation:read'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'équipe hôte')]
    public ?string $teamId = null;

    #[Groups(['conversation:read'])]
    #[ApiProperty(description: 'Identifiant UUID du loueur (guest)')]
    public ?string $guestUserId = null;

    #[Groups(['conversation:read'])]
    #[ApiProperty(description: 'Date et heure de création (ISO 8601)')]
    public ?string $createdAt = null;

    /** @var MessageOutput[] */
    #[Groups(['conversation:read'])]
    #[ApiProperty(description: 'Messages de la conversation, triés du plus ancien au plus récent')]
    public array $messages = [];

    public static function fromEntity(object $entity): static
    {
        if (!$entity instanceof Conversation) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', Conversation::class, get_debug_type($entity)));
        }

        $output = new static();
        $output->id = $entity->getId()->toString();
        $output->reservationId = $entity->getReservationId()->toRfc4122();
        $output->accommodationId = $entity->getAccommodationId()->toRfc4122();
        $output->teamId = $entity->getTeamId()->toRfc4122();
        $output->guestUserId = $entity->getGuestUserId()->toRfc4122();
        $output->createdAt = $entity->getCreatedAt()->format(\DateTimeInterface::ATOM);
        $output->messages = array_map(
            static fn ($message) => MessageOutput::fromEntity($message),
            $entity->getMessages(),
        );

        return $output;
    }
}
