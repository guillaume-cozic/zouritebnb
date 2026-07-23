<?php

declare(strict_types=1);

namespace App\Contact\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * The precise validation rules (non-empty name/subject/message, well-formed
 * email) are enforced by the domain entity {@see \App\Contact\Domain\Entity\ContactMessage}.
 */
final readonly class SendContactMessageInput
{
    public function __construct(
        #[Groups(['contact_message:write'])]
        #[ApiProperty(description: 'Nom de la personne qui envoie le message.', example: 'Marie Dupont')]
        public string $name = '',

        #[Groups(['contact_message:write'])]
        #[ApiProperty(description: 'Adresse e-mail de contact pour la réponse.', example: 'marie.dupont@example.com')]
        public string $email = '',

        #[Groups(['contact_message:write'])]
        #[ApiProperty(description: 'Sujet du message.', example: 'Question sur une réservation')]
        public string $subject = '',

        #[Groups(['contact_message:write'])]
        #[ApiProperty(description: 'Contenu du message adressé à la plateforme.', example: 'Bonjour, je souhaite savoir s\'il est possible de modifier les dates de ma réservation. Merci !')]
        public string $message = '',
    ) {
    }
}
