<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'HostRevenue',
    operations: [
        new Get(
            uriTemplate: '/host/revenue',
            openapi: new OpenApiOperation(
                summary: 'Tableau de bord des revenus de l\'hôte',
                description: 'Retourne le détail des revenus et versements de l\'équipe hôte authentifiée, '
                    .'dérivé de ses réservations confirmées. La commission (8 %) et le don solidaire (7 %) '
                    .'sont des surcharges payées en plus par le voyageur : l\'hôte perçoit l\'intégralité du '
                    .'prix du séjour (total_price). Un versement est « en attente » tant que le séjour n\'est '
                    .'pas terminé, puis « disponible » une fois le départ passé. Réservé à un utilisateur '
                    .'authentifié (résultats limités à sa propre équipe).',
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            normalizationContext: ['groups' => ['host_revenue:read'], 'skip_null_values' => false],
            provider: HostRevenueProvider::class,
        ),
    ],
)]
final class HostRevenueOutput
{
    #[Groups(['host_revenue:read'])]
    #[ApiProperty(identifier: true, description: 'Identifiant logique du tableau de bord', example: 'current')]
    public string $id = 'current';

    #[Groups(['host_revenue:read'])]
    #[ApiProperty(description: 'Revenu total brut de l\'hôte (somme des prix de séjour des réservations confirmées), en euros', example: 12450.0)]
    public float $totalEarned = 0.0;

    #[Groups(['host_revenue:read'])]
    #[ApiProperty(description: 'Montant en attente de versement (séjours confirmés pas encore terminés), en euros', example: 3200.0)]
    public float $pendingAmount = 0.0;

    #[Groups(['host_revenue:read'])]
    #[ApiProperty(description: 'Montant disponible (séjours confirmés et terminés), en euros', example: 9250.0)]
    public float $availableAmount = 0.0;

    #[Groups(['host_revenue:read'])]
    #[ApiProperty(description: 'Nombre de réservations confirmées prises en compte', example: 42)]
    public int $confirmedReservations = 0;

    #[Groups(['host_revenue:read'])]
    #[ApiProperty(description: 'Nombre de séjours à venir (réservations confirmées dont le départ est dans le futur)', example: 8)]
    public int $upcomingStays = 0;

    /**
     * @var array<array{accommodationId: ?string, title: ?string, amount: float, reservations: int}>
     */
    #[Groups(['host_revenue:read'])]
    #[ApiProperty(
        description: 'Revenu confirmé réparti par hébergement',
        example: [['accommodationId' => '01961e2f-dead-7000-beef-000000000002', 'title' => 'Studio vue mer', 'amount' => 4800.0, 'reservations' => 12]],
    )]
    public array $byAccommodation = [];

    /**
     * @var array<array{month: string, amount: float}>
     */
    #[Groups(['host_revenue:read'])]
    #[ApiProperty(
        description: 'Échéancier des versements : revenu confirmé réparti par mois de fin de séjour (format YYYY-MM)',
        example: [['month' => '2026-05', 'amount' => 1600.0]],
    )]
    public array $byMonth = [];

    /**
     * @var array<array{reservationId: string, accommodationTitle: ?string, guestName: string, checkIn: string, checkOut: string, amount: float, status: string}>
     */
    #[Groups(['host_revenue:read'])]
    #[ApiProperty(
        description: 'Relevé des versements ligne à ligne : une entrée par réservation confirmée, avec son statut de versement ("pending" tant que le séjour n\'est pas terminé, "available" ensuite)',
        example: [[
            'reservationId' => '01961e2f-dead-7000-beef-000000000001',
            'accommodationTitle' => 'Studio vue mer',
            'guestName' => 'Jean Dupont',
            'checkIn' => '2026-05-01T15:00:00+00:00',
            'checkOut' => '2026-05-05T11:00:00+00:00',
            'amount' => 400.0,
            'status' => 'available',
        ]],
    )]
    public array $payouts = [];
}
