<?php

declare(strict_types=1);

namespace App\Payment\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'PaymentIntent',
    operations: [
        new Post(
            uriTemplate: '/payment-intents',
            openapi: new OpenApiOperation(
                summary: 'Créer un PaymentIntent Stripe en pré-autorisation',
                description: 'Crée un PaymentIntent Stripe en mode "capture manuelle" : la carte du voyageur est validée mais aucun débit n\'a lieu tant que l\'hôte n\'a pas confirmé la réservation. Renvoie le client_secret nécessaire pour finaliser la confirmation côté frontend avec Stripe Elements.',
            ),
            denormalizationContext: ['groups' => ['payment_intent:write']],
            normalizationContext: ['groups' => ['payment_intent:read']],
            input: PaymentIntentInput::class,
            output: PaymentIntentOutput::class,
            processor: CreatePaymentIntentProcessor::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
        ),
    ],
)]
final class PaymentIntentOutput
{
    #[Groups(['payment_intent:read'])]
    #[ApiProperty(description: 'Identifiant Stripe du PaymentIntent créé.', example: 'pi_3Pq...AbCd')]
    public ?string $paymentIntentId = null;

    #[Groups(['payment_intent:read'])]
    #[ApiProperty(description: 'Secret client à transmettre à Stripe.js côté frontend pour confirmer le paiement.', example: 'pi_3Pq...AbCd_secret_xyz')]
    public ?string $clientSecret = null;
}
