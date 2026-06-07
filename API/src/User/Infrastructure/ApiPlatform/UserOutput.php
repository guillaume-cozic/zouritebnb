<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'User',
    operations: [
        new Post(
            uriTemplate: '/register',
            status: 201,
            openapi: new OpenApiOperation(
                summary: 'Inscription d\'un utilisateur',
                description: 'Crée un utilisateur et son équipe associée. L\'utilisateur est le seul membre de sa team à la création.',
            ),
            normalizationContext: ['groups' => ['user:read']],
            denormalizationContext: ['groups' => ['user:write']],
            input: RegisterUserInput::class,
            processor: RegisterUserProcessor::class,
        ),
        new Post(
            uriTemplate: '/login',
            openapi: new OpenApiOperation(
                summary: 'Authentification d\'un utilisateur',
                description: 'Vérifie email + mot de passe et retourne l\'utilisateur ainsi qu\'un JWT (champ `token`) à utiliser comme Bearer.',
            ),
            normalizationContext: ['groups' => ['user:read', 'user:token']],
            denormalizationContext: ['groups' => ['user:write']],
            input: LoginUserInput::class,
            processor: LoginUserProcessor::class,
        ),
        new Patch(
            uriTemplate: '/users/profile',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Mettre à jour le profil de l\'utilisateur courant',
                description: 'Met à jour le prénom, le nom et l\'email de l\'utilisateur authentifié (identifié via le JWT).',
            ),
            denormalizationContext: ['groups' => ['user:write']],
            read: false,
            input: UpdateUserProfileInput::class,
            output: false,
            processor: UpdateUserProfileProcessor::class,
        ),
    ],
)]
class UserOutput
{
    #[Groups(['user:read'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'utilisateur', example: '019cf27a-96ba-7957-8622-eeccb7350e79')]
    public ?string $id = null;

    #[Groups(['user:read'])]
    #[ApiProperty(description: 'Adresse email', example: 'host@example.com')]
    public ?string $email = null;

    #[Groups(['user:read'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'équipe de l\'utilisateur', example: '019cf27a-96ba-7957-8622-eeccb7350e99')]
    public ?string $teamId = null;

    #[Groups(['user:read'])]
    #[ApiProperty(description: 'Prénom', example: 'Marie')]
    public ?string $firstName = null;

    #[Groups(['user:read'])]
    #[ApiProperty(description: 'Nom', example: 'Dupont')]
    public ?string $lastName = null;

    #[Groups(['user:token'])]
    #[ApiProperty(description: 'JWT Bearer à placer dans l\'en-tête Authorization pour les requêtes authentifiées', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...')]
    public ?string $token = null;
}
