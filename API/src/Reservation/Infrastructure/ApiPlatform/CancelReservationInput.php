<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CancelReservationInput
{
    public function __construct(
        #[Groups(['reservation:write'])]
        #[ApiProperty(description: 'Message facultatif accompagnant l\'annulation, publié dans la conversation liée à la réservation.', example: 'Bonjour, un imprévu m\'oblige à annuler, désolé pour le dérangement.')]
        #[Assert\Length(max: 5000)]
        public ?string $message = null,
    ) {
    }
}
