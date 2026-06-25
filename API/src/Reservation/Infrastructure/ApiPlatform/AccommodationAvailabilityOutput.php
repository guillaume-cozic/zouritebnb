<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AccommodationAvailability',
    operations: [
        new Get(
            uriTemplate: '/accommodations/{accommodationId}/availability',
            openapi: new OpenApiOperation(
                summary: 'Disponibilités d\'un hébergement',
                description: 'Retourne les plages de dates déjà réservées (réservations en statut "pending" ou "confirmed" dont le séjour n\'est pas terminé) pour un hébergement. Endpoint public, exposant uniquement les dates occupées — aucune donnée voyageur ni tarif. Utilisé par la page de détail pour barrer les dates indisponibles. Une plage couvre les nuits occupées : checkIn inclus, checkOut exclu (jour de départ libre pour une nouvelle arrivée). Un identifiant invalide ou inconnu retourne une liste vide.',
            ),
            normalizationContext: ['groups' => ['accommodation_availability:read'], 'skip_null_values' => false],
            provider: AccommodationAvailabilityProvider::class,
        ),
    ],
)]
final class AccommodationAvailabilityOutput
{
    #[Groups(['accommodation_availability:read'])]
    #[ApiProperty(identifier: true, description: 'Identifiant UUID de l\'hébergement', example: '01961e2f-dead-7000-beef-000000000002')]
    public string $accommodationId = '';

    /**
     * @var array<array{checkIn: string, checkOut: string}>
     */
    #[Groups(['accommodation_availability:read'])]
    #[ApiProperty(
        description: 'Plages de dates indisponibles (format ISO AAAA-MM-JJ). checkIn correspond à la première nuit occupée, checkOut au jour de départ (exclu des nuits réservées).',
        example: [['checkIn' => '2026-05-01', 'checkOut' => '2026-05-05']],
    )]
    public array $busyRanges = [];
}
