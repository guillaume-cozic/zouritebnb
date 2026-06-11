<?php

declare(strict_types=1);

namespace App\Payment\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PaymentIntentInput
{
    /**
     * @param array<string, string|int|float|bool|null> $metadata
     */
    public function __construct(
        #[Groups(['payment_intent:write'])]
        #[ApiProperty(description: 'Montant total à autoriser, exprimé en centimes de la devise choisie.', example: 25000)]
        #[Assert\Positive]
        public int $amountCents = 0,

        #[Groups(['payment_intent:write'])]
        #[ApiProperty(description: 'Code ISO 4217 de la devise sur 3 lettres (insensible à la casse).', example: 'eur')]
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^[a-zA-Z]{3}$/', message: 'La devise doit être un code ISO 4217 de 3 lettres.')]
        public string $currency = '',

        #[Groups(['payment_intent:write'])]
        #[ApiProperty(description: 'Description du paiement affichée dans Stripe et sur le reçu.', example: 'Réservation Maison du lagon — 10 au 15 juin 2026')]
        public string $description = '',

        /**
         * @var array<string, string|int|float|bool|null>
         */
        #[Groups(['payment_intent:write'])]
        #[ApiProperty(description: 'Métadonnées arbitraires transmises à Stripe (ex: identifiant logement, dates, voyageur).')]
        public array $metadata = [],
    ) {
    }
}
