<?php

declare(strict_types=1);

namespace App\Donation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Example;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\RequestBody;
use Symfony\Component\Serializer\Attribute\Groups;

// Endpoint volontairement public (pas d'attribut `security`) : le don est
// anonyme, aucun compte voyageur n'est requis pour soutenir un projet solidaire.
#[ApiResource(
    shortName: 'DonationIntent',
    operations: [
        new Post(
            uriTemplate: '/donation-intents',
            openapi: new OpenApiOperation(
                summary: 'Créer un PaymentIntent Stripe pour un don à un projet solidaire',
                description: 'Crée un PaymentIntent Stripe en capture automatique pour un don d\'un montant libre à un projet solidaire actif : le donateur est débité dès qu\'il confirme le paiement (contrairement aux réservations, en capture manuelle). Le montant est exprimé en centimes d\'euro et doit être compris entre 100 (1 €) et 1 000 000 (10 000 €) ; la devise est toujours l\'euro. Endpoint public : aucun compte n\'est requis, le don est anonyme. Renvoie le client_secret nécessaire pour finaliser la confirmation côté frontend avec Stripe Elements.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Don valide de 5 €',
                                    value: [
                                        'solidarityProjectId' => '019cf27a-96ba-7957-8622-eeccb7350e79',
                                        'amountCents' => 500,
                                    ],
                                ),
                                'amount_below_minimum' => new Example(
                                    summary: 'Invalide : montant inférieur à 1 €',
                                    description: 'Renvoie une erreur 422 : le montant du don doit être d\'au moins 100 centimes (1 €).',
                                    value: [
                                        'solidarityProjectId' => '019cf27a-96ba-7957-8622-eeccb7350e79',
                                        'amountCents' => 99,
                                    ],
                                ),
                                'amount_above_maximum' => new Example(
                                    summary: 'Invalide : montant supérieur à 10 000 €',
                                    description: 'Renvoie une erreur 422 : le montant du don ne doit pas dépasser 1 000 000 centimes (10 000 €).',
                                    value: [
                                        'solidarityProjectId' => '019cf27a-96ba-7957-8622-eeccb7350e79',
                                        'amountCents' => 1000001,
                                    ],
                                ),
                                'project_not_donatable' => new Example(
                                    summary: 'Invalide : projet inexistant ou clôturé',
                                    description: 'Renvoie une erreur 422 : le projet solidaire n\'existe pas ou n\'accepte plus de dons (statut différent de « active »).',
                                    value: [
                                        'solidarityProjectId' => '00000000-0000-4000-8000-000000000000',
                                        'amountCents' => 500,
                                    ],
                                ),
                                'invalid_project_id' => new Example(
                                    summary: 'Invalide : identifiant de projet non UUID',
                                    description: 'Renvoie une erreur 422 avec une violation sur solidarityProjectId : l\'identifiant doit être un UUID valide.',
                                    value: [
                                        'solidarityProjectId' => 'not-a-uuid',
                                        'amountCents' => 500,
                                    ],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            denormalizationContext: ['groups' => ['donation_intent:write']],
            normalizationContext: ['groups' => ['donation_intent:read']],
            input: CreateDonationIntentInput::class,
            output: DonationIntentOutput::class,
            processor: CreateDonationIntentProcessor::class,
            read: false,
        ),
    ],
)]
final class DonationIntentOutput
{
    #[Groups(['donation_intent:read'])]
    #[ApiProperty(description: 'Identifiant Stripe du PaymentIntent créé pour le don.', example: 'pi_3Pq...AbCd')]
    public ?string $paymentIntentId = null;

    #[Groups(['donation_intent:read'])]
    #[ApiProperty(description: 'Secret client à transmettre à Stripe.js côté frontend pour confirmer le paiement du don.', example: 'pi_3Pq...AbCd_secret_xyz')]
    public ?string $clientSecret = null;
}
