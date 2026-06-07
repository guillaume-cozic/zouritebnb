<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'UserVerification',
    operations: [
        new Post(
            uriTemplate: '/users/{id}/identity-verification',
            status: 200,
            openapi: new OpenApiOperation(
                summary: 'Soumettre une vérification d\'identité',
                description: 'Envoie la pièce d\'identité et le selfie (multipart). La vérification automatique est simulée : l\'utilisateur passe immédiatement au statut "verified". Champs : document (fichier), selfie (fichier), documentType (passport|id_card|driving_license).',
            ),
            inputFormats: ['multipart' => ['multipart/form-data']],
            normalizationContext: ['groups' => ['user_verification:read']],
            deserialize: false,
            read: false,
            input: false,
            processor: SubmitIdentityVerificationProcessor::class,
        ),
        new Get(
            uriTemplate: '/users/{id}/identity-verification',
            openapi: new OpenApiOperation(
                summary: 'Statut de vérification d\'identité d\'un utilisateur',
                description: 'Retourne le statut de vérification courant de l\'utilisateur.',
            ),
            normalizationContext: ['groups' => ['user_verification:read']],
            provider: UserVerificationProvider::class,
        ),
    ],
)]
class UserVerificationOutput
{
    #[Groups(['user_verification:read'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'utilisateur', example: '019cf27a-96ba-7957-8622-eeccb7350e79')]
    public ?string $userId = null;

    #[Groups(['user_verification:read'])]
    #[ApiProperty(description: 'Statut de vérification', example: 'verified')]
    public string $status = 'not_started';

    #[Groups(['user_verification:read'])]
    #[ApiProperty(description: 'Type de pièce d\'identité soumise', example: 'passport')]
    public ?string $documentType = null;

    #[Groups(['user_verification:read'])]
    #[ApiProperty(description: 'Date de vérification (ISO 8601)', example: '2026-06-07T12:00:00+00:00')]
    public ?string $verifiedAt = null;
}
