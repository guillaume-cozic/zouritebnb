<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AdminDashboard',
    operations: [
        new Get(
            uriTemplate: '/admin/dashboard',
            openapi: new OpenApiOperation(
                summary: 'Indicateurs financiers du tableau de bord (administration)',
                description: 'Retourne un aperçu financier de la plateforme : chiffre d\'affaires encaissé, marge (commission), total reversé aux projets solidaires et répartition par projet. Calculé sur les réservations confirmées. Réservé aux administrateurs (ROLE_ADMIN).',
            ),
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['admin_dashboard:read'], 'skip_null_values' => false],
            provider: AdminDashboardProvider::class,
        ),
    ],
)]
final class AdminDashboardOutput
{
    #[Groups(['admin_dashboard:read'])]
    #[ApiProperty(identifier: true, description: 'Identifiant logique du tableau de bord', example: 'current')]
    public string $id = 'current';

    #[Groups(['admin_dashboard:read'])]
    #[ApiProperty(description: 'Chiffre d\'affaires total encaissé (réservations confirmées), en euros', example: 12450.0)]
    public float $totalRevenue = 0.0;

    #[Groups(['admin_dashboard:read'])]
    #[ApiProperty(description: 'Marge totale de la plateforme (commission), en euros', example: 1867.5)]
    public float $totalMargin = 0.0;

    #[Groups(['admin_dashboard:read'])]
    #[ApiProperty(description: 'Total reversé aux projets solidaires, en euros', example: 249.0)]
    public float $totalDonated = 0.0;

    #[Groups(['admin_dashboard:read'])]
    #[ApiProperty(description: 'Nombre de réservations confirmées prises en compte', example: 87)]
    public int $confirmedReservations = 0;

    #[Groups(['admin_dashboard:read'])]
    #[ApiProperty(description: 'Taux de commission appliqué (part du CA)', example: 0.08)]
    public float $commissionRate = 0.0;

    #[Groups(['admin_dashboard:read'])]
    #[ApiProperty(description: 'Taux de reversement aux projets (part du CA)', example: 0.07)]
    public float $donationRate = 0.0;

    /**
     * @var array<array{projectId: string, title: string, amount: float}>
     */
    #[Groups(['admin_dashboard:read'])]
    #[ApiProperty(
        description: 'Total reversé par projet solidaire (attribué au coup de cœur de l\'équipe hôte, sinon au projet par défaut)',
        example: [['projectId' => '01961e2f-dead-7000-beef-000000000001', 'title' => 'Reforestation de Rodrigues', 'amount' => 180.0]],
    )]
    public array $donationsByProject = [];
}
