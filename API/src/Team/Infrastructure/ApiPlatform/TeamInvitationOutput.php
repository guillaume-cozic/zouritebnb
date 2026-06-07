<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Example;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\Team\Domain\Entity\TeamInvitation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'TeamInvitation',
    operations: [
        new GetCollection(
            uriTemplate: '/teams/{id}/invitations',
            openapi: new OpenApiOperation(
                summary: 'Lister les invitations en attente d\'une équipe',
                description: 'Retourne la liste des invitations de co-hôtes en statut "pending" pour l\'équipe.',
            ),
            normalizationContext: ['groups' => ['team_invitation:read']],
            security: 'is_authenticated()',
            provider: TeamInvitationCollectionProvider::class,
        ),
        new Post(
            uriTemplate: '/teams/{id}/invitations',
            status: 201,
            openapi: new OpenApiOperation(
                summary: 'Inviter un co-hôte par email',
                description: 'Crée une invitation en statut "pending" pour un nouveau co-hôte. L\'email doit être valide et ne pas déjà faire l\'objet d\'une invitation en attente pour cette équipe.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: ['email' => 'alice@example.com'],
                                ),
                                'invalid_email' => new Example(
                                    summary: 'Invalide : email mal formé',
                                    description: 'Retourne une erreur 422 car l\'email n\'est pas valide.',
                                    value: ['email' => 'not-an-email'],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            denormalizationContext: ['groups' => ['team:write']],
            normalizationContext: ['groups' => ['team_invitation:read']],
            security: 'is_authenticated()',
            input: InviteCoHostInput::class,
            processor: InviteCoHostProcessor::class,
        ),
        new Delete(
            uriTemplate: '/team-invitations/{id}',
            status: 204,
            read: false,
            openapi: new OpenApiOperation(
                summary: 'Annuler une invitation de co-hôte',
                description: 'Annule une invitation en attente. Retourne une erreur 422 si l\'invitation est introuvable ou déjà finalisée (acceptée ou annulée). Réservé aux membres de l\'équipe propriétaire de l\'invitation.',
            ),
            security: 'is_authenticated()',
            output: false,
            processor: CancelTeamInvitationProcessor::class,
        ),
    ],
)]
class TeamInvitationOutput
{
    #[Groups(['team_invitation:read'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'invitation', example: '019cf27a-96ba-7957-8622-eeccb7350e79')]
    public ?string $id = null;

    #[Groups(['team_invitation:read'])]
    #[ApiProperty(description: 'Adresse email invitée', example: 'alice@example.com')]
    public ?string $email = null;

    #[Groups(['team_invitation:read'])]
    #[ApiProperty(description: 'Statut de l\'invitation (pending, accepted, cancelled)', example: 'pending')]
    public ?string $status = null;

    #[Groups(['team_invitation:read'])]
    #[ApiProperty(description: 'Date de création de l\'invitation (ISO 8601)', example: '2026-04-14T12:00:00+00:00')]
    public ?string $createdAt = null;

    public static function fromDomain(TeamInvitation $invitation): static
    {
        $output = new static();
        $output->id = $invitation->getId()->toRfc4122();
        $output->email = $invitation->getEmail();
        $output->status = $invitation->getStatus()->value;
        $output->createdAt = $invitation->getCreatedAt()->format(\DateTimeInterface::ATOM);

        return $output;
    }
}
