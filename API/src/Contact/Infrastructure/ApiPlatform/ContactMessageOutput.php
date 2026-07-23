<?php

declare(strict_types=1);

namespace App\Contact\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Example;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\RequestBody;

// Endpoint volontairement public (pas d'attribut `security`) : le formulaire de
// contact est accessible à tout visiteur, aucun compte n'est requis pour écrire
// à la plateforme.
#[ApiResource(
    shortName: 'ContactMessage',
    operations: [
        new Post(
            uriTemplate: '/contact_messages',
            status: 201,
            openapi: new OpenApiOperation(
                summary: 'Envoyer un message de contact à la plateforme',
                description: 'Enregistre un message envoyé via le formulaire de contact public. Le nom, le sujet et le message ne doivent pas être vides, et l\'adresse e-mail doit être valide ; sinon la requête est rejetée avec une erreur 422. Endpoint public : aucun compte n\'est requis.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: [
                                        'name' => 'Marie Dupont',
                                        'email' => 'marie.dupont@example.com',
                                        'subject' => 'Question sur une réservation',
                                        'message' => 'Bonjour, je souhaite savoir s\'il est possible de modifier les dates de ma réservation. Merci !',
                                    ],
                                ),
                                'empty_name' => new Example(
                                    summary: 'Invalide : nom vide',
                                    description: 'Renvoie une erreur 422 : le nom de l\'expéditeur est obligatoire.',
                                    value: [
                                        'name' => '',
                                        'email' => 'marie.dupont@example.com',
                                        'subject' => 'Question sur une réservation',
                                        'message' => 'Bonjour, pouvez-vous m\'aider ?',
                                    ],
                                ),
                                'invalid_email' => new Example(
                                    summary: 'Invalide : adresse e-mail mal formée',
                                    description: 'Renvoie une erreur 422 : l\'adresse e-mail doit être une adresse valide.',
                                    value: [
                                        'name' => 'Marie Dupont',
                                        'email' => 'pas-un-email',
                                        'subject' => 'Question sur une réservation',
                                        'message' => 'Bonjour, pouvez-vous m\'aider ?',
                                    ],
                                ),
                                'empty_subject' => new Example(
                                    summary: 'Invalide : sujet vide',
                                    description: 'Renvoie une erreur 422 : le sujet du message est obligatoire.',
                                    value: [
                                        'name' => 'Marie Dupont',
                                        'email' => 'marie.dupont@example.com',
                                        'subject' => '',
                                        'message' => 'Bonjour, pouvez-vous m\'aider ?',
                                    ],
                                ),
                                'empty_message' => new Example(
                                    summary: 'Invalide : message vide',
                                    description: 'Renvoie une erreur 422 : le contenu du message est obligatoire.',
                                    value: [
                                        'name' => 'Marie Dupont',
                                        'email' => 'marie.dupont@example.com',
                                        'subject' => 'Question sur une réservation',
                                        'message' => '',
                                    ],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            denormalizationContext: ['groups' => ['contact_message:write']],
            read: false,
            input: SendContactMessageInput::class,
            output: false,
            processor: SendContactMessageProcessor::class,
        ),
    ],
)]
final class ContactMessageOutput
{
}
