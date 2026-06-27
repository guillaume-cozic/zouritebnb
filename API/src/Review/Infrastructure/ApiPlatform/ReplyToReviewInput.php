<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\OpenApi\Model\Example;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\RequestBody;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'ReviewReply',
    operations: [
        new Patch(
            uriTemplate: '/reviews/{reviewId}/reply',
            status: 204,
            read: false,
            openapi: new OpenApiOperation(
                summary: 'Répondre à un avis (hôte)',
                description: 'Permet à un membre de l\'équipe propriétaire de l\'hébergement de publier (ou de remplacer) une réponse publique à un avis voyageur. Réservé aux avis de type "accommodation". Retourne 403 si l\'utilisateur n\'appartient pas à l\'équipe hôte, 404 si l\'avis est introuvable, 422 si la réponse est vide.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/merge-patch+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: ['reply' => 'Merci beaucoup pour votre séjour, au plaisir de vous accueillir à nouveau !'],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            denormalizationContext: ['groups' => ['review_reply:write']],
            input: self::class,
            output: false,
            processor: ReplyToReviewProcessor::class,
        ),
    ],
)]
final class ReplyToReviewInput
{
    #[Groups(['review_reply:write'])]
    #[ApiProperty(description: 'Réponse publique de l\'hôte à l\'avis.', example: 'Merci pour votre retour, au plaisir de vous revoir !')]
    #[Assert\NotBlank]
    #[Assert\Length(max: 2000)]
    public ?string $reply = null;
}
