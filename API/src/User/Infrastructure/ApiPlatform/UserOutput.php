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
                description: 'Crée un utilisateur et son équipe associée. L\'utilisateur est le seul membre de sa team à la création. La réponse contient un JWT (champ `token`) afin que l\'utilisateur soit connecté immédiatement après l\'inscription.',
            ),
            normalizationContext: ['groups' => ['user:read', 'user:token']],
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
        new Post(
            uriTemplate: '/auth/social',
            openapi: new OpenApiOperation(
                summary: 'Authentification via un fournisseur social (Google, Apple, Facebook)',
                description: 'Vérifie le token émis par le fournisseur (ID token Google, identity token Apple, access token Facebook). Si aucun compte n\'existe pour l\'email attesté, un utilisateur et sa team sont créés (l\'email est marqué vérifié si le fournisseur le garantit). Retourne l\'utilisateur et un JWT (champ `token`) à utiliser comme Bearer.',
            ),
            normalizationContext: ['groups' => ['user:read', 'user:token']],
            denormalizationContext: ['groups' => ['user:write']],
            input: SocialLoginInput::class,
            processor: SocialLoginProcessor::class,
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
        new Post(
            uriTemplate: '/forgot-password',
            status: 202,
            openapi: new OpenApiOperation(
                summary: 'Demander la réinitialisation du mot de passe',
                description: 'Envoie un email contenant un lien de réinitialisation si un compte existe pour cette adresse. Répond toujours 202, que le compte existe ou non, pour ne pas révéler quelles adresses sont enregistrées.',
            ),
            denormalizationContext: ['groups' => ['user:write']],
            read: false,
            input: ForgotPasswordInput::class,
            output: false,
            processor: ForgotPasswordProcessor::class,
        ),
        new Post(
            uriTemplate: '/reset-password',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Réinitialiser le mot de passe',
                description: 'Définit un nouveau mot de passe à partir du jeton reçu par email. Le jeton est à usage unique et expire au bout d\'une heure.',
            ),
            denormalizationContext: ['groups' => ['user:write']],
            read: false,
            input: ResetPasswordInput::class,
            output: false,
            processor: ResetPasswordProcessor::class,
        ),
        new Post(
            uriTemplate: '/verify-email',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Vérifier l\'adresse email',
                description: 'Marque l\'adresse email comme vérifiée à partir du jeton reçu par email. Le jeton est à usage unique et expire au bout de 24 heures.',
            ),
            denormalizationContext: ['groups' => ['user:write']],
            read: false,
            input: VerifyEmailInput::class,
            output: false,
            processor: VerifyEmailProcessor::class,
        ),
        new Post(
            uriTemplate: '/users/resend-verification-email',
            status: 202,
            openapi: new OpenApiOperation(
                summary: 'Renvoyer l\'email de vérification',
                description: 'Renvoie un email de vérification à l\'utilisateur authentifié (par ex. si le lien précédent a expiré). Sans effet si l\'email est déjà vérifié.',
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            input: false,
            output: false,
            processor: ResendVerificationEmailProcessor::class,
        ),
        new Post(
            uriTemplate: '/users/avatar',
            status: 201,
            openapi: new OpenApiOperation(
                summary: 'Téléverser la photo de l\'hôte courant',
                description: 'Envoie une image (multipart/form-data, champ `file`) qui devient la photo de profil publique de l\'utilisateur authentifié. Formats JPEG, PNG ou WebP, 10 Mo maximum. Retourne l\'URL de la photo.',
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            inputFormats: ['multipart' => ['multipart/form-data']],
            deserialize: false,
            read: false,
            input: false,
            output: HostAvatarOutput::class,
            processor: UploadHostAvatarProcessor::class,
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

    #[Groups(['user:read'])]
    #[ApiProperty(description: 'Présentation publique de l\'hôte', example: 'Passionné de randonnée, je loue mon gîte familial depuis 2015.')]
    public ?string $bio = null;

    #[Groups(['user:read'])]
    #[ApiProperty(description: 'URL (relative) de la photo de l\'hôte, ou null', example: '/uploads/photos/019cf27a-96ba-7957-8622-eeccb7350e79.jpg')]
    public ?string $avatarUrl = null;

    #[Groups(['user:read'])]
    #[ApiProperty(description: 'Statut de vérification d\'identité', example: 'verified')]
    public string $verificationStatus = 'not_started';

    #[Groups(['user:read'])]
    #[ApiProperty(description: 'Indique si l\'adresse email a été vérifiée', example: true)]
    public bool $emailVerified = false;

    #[Groups(['user:token'])]
    #[ApiProperty(description: 'JWT Bearer à placer dans l\'en-tête Authorization pour les requêtes authentifiées', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...')]
    public ?string $token = null;

    #[Groups(['user:token'])]
    #[ApiProperty(description: 'Jeton de rafraîchissement (longue durée) à envoyer à POST /api/token/refresh pour obtenir un nouveau JWT sans se reconnecter', example: 'f3a1c2...9b')]
    public ?string $refreshToken = null;
}
