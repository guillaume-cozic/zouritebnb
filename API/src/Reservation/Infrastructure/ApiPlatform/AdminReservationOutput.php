<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AdminReservation',
    operations: [
        new GetCollection(
            uriTemplate: '/admin/reservations',
            openapi: new OpenApiOperation(
                summary: 'Lister toutes les réservations (administration)',
                description: 'Retourne la liste complète des réservations de la plateforme, triées par date d\'arrivée décroissante, avec le titre de l\'hébergement associé. Endpoint en lecture seule réservé aux administrateurs (ROLE_ADMIN).',
            ),
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['admin_reservation:list'], 'skip_null_values' => false],
            provider: AdminReservationCollectionProvider::class,
            paginationEnabled: true,
            paginationItemsPerPage: 20,
            paginationClientItemsPerPage: true,
            paginationMaximumItemsPerPage: 100,
        ),
    ],
)]
final class AdminReservationOutput
{
    #[Groups(['admin_reservation:list'])]
    #[ApiProperty(description: 'Identifiant unique de la réservation (UUID)', example: '01961e2f-dead-7000-beef-000000000001')]
    public ?string $id = null;

    #[Groups(['admin_reservation:list'])]
    #[ApiProperty(description: 'Nom du voyageur', example: 'Jean Dupont')]
    public ?string $guestName = null;

    #[Groups(['admin_reservation:list'])]
    #[ApiProperty(description: 'Identifiant UUID du compte voyageur (null si réservation hors compte)', example: '01961e2f-dead-7000-beef-0000000000c1')]
    public ?string $guestUserId = null;

    #[Groups(['admin_reservation:list'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'hébergement réservé', example: '01961e2f-dead-7000-beef-0000000000a1')]
    public ?string $accommodationId = null;

    #[Groups(['admin_reservation:list'])]
    #[ApiProperty(description: 'Titre de l\'hébergement réservé (null si l\'hébergement a été supprimé)', example: 'Villa avec vue sur le lagon')]
    public ?string $accommodationTitle = null;

    #[Groups(['admin_reservation:list'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'équipe hôte', example: '01961e2f-dead-7000-beef-0000000000b1')]
    public ?string $teamId = null;

    #[Groups(['admin_reservation:list'])]
    #[ApiProperty(description: 'Date d\'arrivée (ISO 8601)', example: '2026-05-01T15:00:00+00:00')]
    public ?string $checkIn = null;

    #[Groups(['admin_reservation:list'])]
    #[ApiProperty(description: 'Date de départ (ISO 8601)', example: '2026-05-05T11:00:00+00:00')]
    public ?string $checkOut = null;

    #[Groups(['admin_reservation:list'])]
    #[ApiProperty(description: 'Statut de la réservation (pending, confirmed, refused, cancelled, expired)', example: 'confirmed')]
    public ?string $status = null;

    #[Groups(['admin_reservation:list'])]
    #[ApiProperty(description: 'Prix total du séjour en euros', example: 400.0)]
    public ?float $totalPrice = null;

    #[Groups(['admin_reservation:list'])]
    #[ApiProperty(description: 'Prix par nuit en euros', example: 100.0)]
    public ?float $pricePerNight = null;

    #[Groups(['admin_reservation:list'])]
    #[ApiProperty(description: 'Pourcentage de remise appliqué (null si aucune remise)', example: 10.0)]
    public ?float $appliedDiscountPercentage = null;
}
