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
    #[ApiProperty(description: 'Corps du message (null pour un message ne contenant qu\'une photo)')]
    public ?string $body = null;

    #[Groups(['conversation:read'])]
    #[ApiProperty(description: 'URL relative de la photo jointe (null si le message n\'en contient pas)', example: '/uploads/photos/0197b1c2-1111-7000-8000-000000000000.webp')]
    public ?string $attachmentUrl = null;

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
        $output->body = $message->getBody()?->toString();
        $attachment = $message->getAttachment();
        $output->attachmentUrl = null !== $attachment ? '/uploads/photos/'.$attachment->filename() : null;
        $output->authorUserId = $message->getAuthorUserId()?->toRfc4122();
        $output->sentAt = $message->getSentAt()->format(\DateTimeInterface::ATOM);
        $output->isSystem = $message->isSystem();

        return $output;
    }
}
