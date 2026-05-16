<?php

declare(strict_types=1);

namespace App\Conversation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use App\Conversation\Domain\Entity\Message;
use Symfony\Component\Serializer\Attribute\Groups;

final class MessageOutput
{
    #[Groups(['conversation:read'])]
    #[ApiProperty(description: 'Identifiant unique du message (UUID)')]
    public ?string $id = null;

    #[Groups(['conversation:read'])]
    #[ApiProperty(description: 'Corps du message')]
    public ?string $body = null;

    #[Groups(['conversation:read'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'auteur (null pour les messages système)')]
    public ?string $authorUserId = null;

    #[Groups(['conversation:read'])]
    #[ApiProperty(description: 'Date et heure d\'envoi (ISO 8601)')]
    public ?string $sentAt = null;

    #[Groups(['conversation:read'])]
    #[ApiProperty(description: 'Vrai si le message a été posté automatiquement par le système')]
    public bool $isSystem = false;

    public static function fromEntity(Message $message): self
    {
        $output = new self();
        $output->id = $message->getId()->toString();
        $output->body = $message->getBody()->toString();
        $output->authorUserId = $message->getAuthorUserId()?->toRfc4122();
        $output->sentAt = $message->getSentAt()->format(\DateTimeInterface::ATOM);
        $output->isSystem = $message->isSystem();

        return $output;
    }
}
