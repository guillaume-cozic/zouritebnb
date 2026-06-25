<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'WishlistItem',
    operations: [
        new GetCollection(
            uriTemplate: '/wishlist',
            openapi: new OpenApiOperation(
                summary: 'Lister la wishlist',
                description: 'Retourne les hébergements sauvegardés par le propriétaire courant. Pour un utilisateur authentifié, sa wishlist de compte ; sinon celle associée à l\'identifiant de corrélation envoyé dans l\'en-tête X-Wishlist-Id (cookie côté client). Liste vide si visiteur anonyme sans identifiant.',
            ),
            normalizationContext: ['groups' => ['wishlist:read']],
            provider: WishlistCollectionProvider::class,
        ),
        new Post(
            uriTemplate: '/wishlist',
            status: 201,
            openapi: new OpenApiOperation(
                summary: 'Ajouter un hébergement à la wishlist',
                description: 'Ajoute un hébergement à la wishlist du propriétaire courant (utilisateur authentifié, ou visiteur anonyme via l\'en-tête X-Wishlist-Id). Opération idempotente : ajouter un hébergement déjà présent ne crée pas de doublon. Retourne 422 si l\'hébergement est introuvable, 400 si aucun propriétaire ne peut être déterminé.',
            ),
            denormalizationContext: ['groups' => ['wishlist:write']],
            normalizationContext: ['groups' => ['wishlist:read']],
            input: AddWishlistItemInput::class,
            processor: AddWishlistItemProcessor::class,
        ),
        new Delete(
            uriTemplate: '/wishlist/{accommodationId}',
            status: 204,
            read: false,
            output: false,
            openapi: new OpenApiOperation(
                summary: 'Retirer un hébergement de la wishlist',
                description: 'Retire un hébergement de la wishlist du propriétaire courant. Opération idempotente : retirer un hébergement absent ne renvoie pas d\'erreur.',
            ),
            processor: RemoveWishlistItemProcessor::class,
        ),
        new Post(
            uriTemplate: '/wishlist/merge',
            status: 204,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            openapi: new OpenApiOperation(
                summary: 'Fusionner la wishlist anonyme dans le compte',
                description: 'Rattache les hébergements de la wishlist anonyme (identifiant de corrélation envoyé dans l\'en-tête X-Wishlist-Id) au compte de l\'utilisateur authentifié, en supprimant les doublons. Appelé après la connexion, puis le cookie de corrélation est nettoyé côté client.',
            ),
            input: false,
            output: false,
            read: false,
            processor: MergeWishlistProcessor::class,
        ),
    ],
)]
final class WishlistItemOutput
{
    #[Groups(['wishlist:read'])]
    #[ApiProperty(identifier: true, description: 'Identifiant UUID de l\'hébergement sauvegardé', example: '01961e2f-dead-7000-beef-000000000001')]
    public ?string $accommodationId = null;

    #[Groups(['wishlist:read'])]
    #[ApiProperty(description: 'Titre de l\'hébergement', example: 'Loft lumineux au cœur de Saint-Denis')]
    public ?string $title = null;

    #[Groups(['wishlist:read'])]
    #[ApiProperty(description: 'Ville de l\'hébergement', example: 'Saint-Denis')]
    public ?string $city = null;

    #[Groups(['wishlist:read'])]
    #[ApiProperty(description: 'Pays de l\'hébergement', example: 'La Réunion')]
    public ?string $country = null;

    #[Groups(['wishlist:read'])]
    #[ApiProperty(description: 'Prix par nuit, en euros', example: 120.0)]
    public ?float $price = null;

    #[Groups(['wishlist:read'])]
    #[ApiProperty(description: 'URL relative d\'une photo de l\'hébergement (null si aucune)', example: '/uploads/photos/abc.jpg')]
    public ?string $photoUrl = null;
}
