<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AdminUser',
    operations: [
        new GetCollection(
            uriTemplate: '/admin/users',
            openapi: new OpenApiOperation(
                summary: 'Lister tous les utilisateurs (administration)',
                description: 'Retourne la liste complète des utilisateurs de la plateforme, triés par email, avec leurs rôles, leur statut de vérification d\'identité et leurs compteurs d\'activité (hébergements de leur équipe, réservations effectuées). Le mot de passe haché n\'est jamais exposé. Endpoint en lecture seule réservé aux administrateurs (ROLE_ADMIN).',
            ),
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['admin_user:list'], 'skip_null_values' => false],
            provider: AdminUserCollectionProvider::class,
            paginationEnabled: false,
        ),
    ],
)]
final class AdminUserOutput
{
    #[Groups(['admin_user:list'])]
    #[ApiProperty(description: 'Identifiant unique de l\'utilisateur (UUID)', example: '01961e2f-dead-7000-beef-0000000000c1')]
    public ?string $id = null;

    #[Groups(['admin_user:list'])]
    #[ApiProperty(description: 'Adresse email de l\'utilisateur', example: 'marie.dupont@example.com')]
    public ?string $email = null;

    #[Groups(['admin_user:list'])]
    #[ApiProperty(description: 'Prénom de l\'utilisateur', example: 'Marie')]
    public ?string $firstName = null;

    #[Groups(['admin_user:list'])]
    #[ApiProperty(description: 'Nom de famille de l\'utilisateur', example: 'Dupont')]
    public ?string $lastName = null;

    /**
     * @var string[]
     */
    #[Groups(['admin_user:list'])]
    #[ApiProperty(
        description: 'Rôles attribués à l\'utilisateur (ROLE_USER est implicite)',
        example: ['ROLE_ADMIN'],
        openapiContext: [
            'type' => 'array',
            'items' => ['type' => 'string'],
        ],
    )]
    public array $roles = [];

    #[Groups(['admin_user:list'])]
    #[ApiProperty(description: 'Statut de la vérification d\'identité (not_started, pending, verified, rejected)', example: 'verified')]
    public ?string $verificationStatus = null;

    #[Groups(['admin_user:list'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'équipe hôte de l\'utilisateur', example: '01961e2f-dead-7000-beef-0000000000b1')]
    public ?string $teamId = null;

    #[Groups(['admin_user:list'])]
    #[ApiProperty(description: 'Nombre d\'hébergements appartenant à l\'équipe de l\'utilisateur', example: 3)]
    public ?int $accommodationCount = null;

    #[Groups(['admin_user:list'])]
    #[ApiProperty(description: 'Nombre de réservations effectuées par l\'utilisateur en tant que voyageur', example: 5)]
    public ?int $reservationCount = null;
}
