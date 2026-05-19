<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Example;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\Reservation\Domain\Entity\Reservation;
use App\Shared\ApiPlatform\State\FromEntityInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'Reservation',
    operations: [
        new Get(
            uriTemplate: '/reservations/{id}',
            openapi: new OpenApiOperation(
                summary: 'Récupérer une réservation',
                description: 'Retourne le détail complet d\'une réservation par son identifiant UUID. Retourne 404 si la réservation est introuvable.',
            ),
            normalizationContext: ['groups' => ['reservation:read']],
            provider: ReservationItemProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/reservations',
            openapi: new OpenApiOperation(
                summary: 'Lister les réservations',
                description: 'Retourne la liste des réservations de l\'équipe courante. Filtres optionnels : accommodationId (UUID de l\'hébergement), from et to (intervalle de dates ISO 8601 — les réservations qui chevauchent cet intervalle sont retournées).',
            ),
            normalizationContext: ['groups' => ['reservation:read']],
            provider: ReservationCollectionProvider::class,
        ),
        new Post(
            uriTemplate: '/reservations',
            status: 201,
            openapi: new OpenApiOperation(
                summary: 'Créer une réservation (back-office)',
                description: 'Crée une réservation directement en statut "confirmed" depuis le back-office hôte, sans étape d\'approbation (aucun loueur associé : guestUserId reste null). Pour le parcours public où un loueur soumet une demande à valider par l\'hôte, utiliser POST /reservations/request. La date de départ doit être strictement postérieure à la date d\'arrivée et le nom du voyageur ne peut pas être vide.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: [
                                        'accommodationId' => '01961e2f-dead-7000-beef-000000000001',
                                        'checkIn' => '2026-05-01T15:00:00+00:00',
                                        'checkOut' => '2026-05-05T11:00:00+00:00',
                                        'guestName' => 'Jean Dupont',
                                    ],
                                ),
                                'invalid_date_range' => new Example(
                                    summary: 'Invalide : plage de dates invalide',
                                    description: 'Retourne une erreur 422 car la date de départ n\'est pas strictement postérieure à la date d\'arrivée.',
                                    value: [
                                        'accommodationId' => '01961e2f-dead-7000-beef-000000000001',
                                        'checkIn' => '2026-05-05T15:00:00+00:00',
                                        'checkOut' => '2026-05-01T11:00:00+00:00',
                                        'guestName' => 'Jean Dupont',
                                    ],
                                ),
                                'empty_guest_name' => new Example(
                                    summary: 'Invalide : nom du voyageur vide',
                                    description: 'Retourne une erreur 422 car le nom du voyageur est obligatoire.',
                                    value: [
                                        'accommodationId' => '01961e2f-dead-7000-beef-000000000001',
                                        'checkIn' => '2026-05-01T15:00:00+00:00',
                                        'checkOut' => '2026-05-05T11:00:00+00:00',
                                        'guestName' => '',
                                    ],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            denormalizationContext: ['groups' => ['reservation:write']],
            normalizationContext: ['groups' => ['reservation:read']],
            input: ReservationInput::class,
            processor: CreateReservationProcessor::class,
        ),
        new Patch(
            uriTemplate: '/reservations/{id}/confirm',
            read: false,
            openapi: new OpenApiOperation(
                summary: 'Confirmer une réservation',
                description: 'Passe une réservation du statut "pending" au statut "confirmed". Retourne 404 si la réservation est introuvable, 422 si elle est déjà confirmée ou annulée.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/merge-patch+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: new \ArrayObject(),
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            denormalizationContext: ['groups' => ['reservation:write']],
            normalizationContext: ['groups' => ['reservation:read']],
            input: false,
            processor: ConfirmReservationProcessor::class,
        ),
        new Patch(
            uriTemplate: '/reservations/{id}/cancel',
            read: false,
            openapi: new OpenApiOperation(
                summary: 'Annuler une réservation',
                description: 'Passe une réservation au statut "cancelled". Retourne 404 si la réservation est introuvable, 422 si elle est déjà annulée.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/merge-patch+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: new \ArrayObject(),
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            denormalizationContext: ['groups' => ['reservation:write']],
            normalizationContext: ['groups' => ['reservation:read']],
            input: false,
            processor: CancelReservationProcessor::class,
        ),
        new Post(
            uriTemplate: '/reservations/request',
            status: 201,
            openapi: new OpenApiOperation(
                summary: 'Demander une réservation (parcours B2C)',
                description: 'Crée une demande de réservation en statut "pending" depuis le parcours public. Une conversation entre l\'hôte et le loueur est ouverte automatiquement. L\'hôte dispose de 24h pour accepter ou refuser. La date de départ doit être strictement postérieure à la date d\'arrivée.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: [
                                        'accommodationId' => '01961e2f-dead-7000-beef-000000000001',
                                        'guestUserId' => '01961e2f-dead-7000-beef-0000000000c1',
                                        'checkIn' => '2026-05-01T15:00:00+00:00',
                                        'checkOut' => '2026-05-05T11:00:00+00:00',
                                        'guestName' => 'Jean Dupont',
                                    ],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            denormalizationContext: ['groups' => ['reservation:write']],
            normalizationContext: ['groups' => ['reservation:read']],
            input: RequestReservationInput::class,
            processor: RequestReservationProcessor::class,
        ),
        new Patch(
            uriTemplate: '/reservations/{id}/refuse',
            read: false,
            openapi: new OpenApiOperation(
                summary: 'Refuser une réservation (hôte)',
                description: 'L\'hôte refuse une réservation en statut "pending". Passe au statut "refused". Retourne 404 si introuvable, 422 si la réservation n\'est pas en "pending".',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/merge-patch+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: new \ArrayObject(),
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            denormalizationContext: ['groups' => ['reservation:write']],
            normalizationContext: ['groups' => ['reservation:read']],
            input: false,
            processor: RefuseReservationProcessor::class,
        ),
    ],
)]
class ReservationOutput implements FromEntityInterface
{
    #[Groups(['reservation:read'])]
    #[ApiProperty(description: 'Identifiant unique (UUID)', example: '01961e2f-dead-7000-beef-000000000001')]
    public ?string $id = null;

    #[Groups(['reservation:read'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'hébergement réservé', example: '01961e2f-dead-7000-beef-000000000002')]
    public ?string $accommodationId = null;

    #[Groups(['reservation:read'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'équipe propriétaire', example: '00000000-0000-4000-8000-000000000001')]
    public ?string $teamId = null;

    #[Groups(['reservation:read'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'utilisateur loueur (null pour les réservations créées en back office)', example: '01961e2f-dead-7000-beef-0000000000c1')]
    public ?string $guestUserId = null;

    #[Groups(['reservation:read'])]
    #[ApiProperty(description: 'Date et heure d\'arrivée (ISO 8601)', example: '2026-05-01T15:00:00+00:00')]
    public ?string $checkIn = null;

    #[Groups(['reservation:read'])]
    #[ApiProperty(description: 'Date et heure de départ (ISO 8601)', example: '2026-05-05T11:00:00+00:00')]
    public ?string $checkOut = null;

    #[Groups(['reservation:read'])]
    #[ApiProperty(description: 'Nom du voyageur principal', example: 'Jean Dupont')]
    public ?string $guestName = null;

    #[Groups(['reservation:read'])]
    #[ApiProperty(description: 'Statut de la réservation (pending, confirmed, cancelled, refused)', example: 'pending')]
    public ?string $status = null;

    #[Groups(['reservation:read'])]
    #[ApiProperty(description: 'Prix total du séjour (remise hebdomadaire incluse si applicable)', example: 400.0)]
    public ?float $totalPrice = null;

    #[Groups(['reservation:read'])]
    #[ApiProperty(description: 'Prix par nuit de l\'hébergement au moment de la réservation', example: 100.0)]
    public ?float $pricePerNight = null;

    #[Groups(['reservation:read'])]
    #[ApiProperty(description: 'Pourcentage de remise appliqué (null si aucune remise)', example: 20.0)]
    public ?float $appliedDiscountPercentage = null;

    public static function fromEntity(object $entity): static
    {
        \assert($entity instanceof Reservation);

        $output = new static();
        $output->id = $entity->getId()->toString();
        $output->accommodationId = $entity->getAccommodationId()->toRfc4122();
        $output->teamId = $entity->getTeamId()->toRfc4122();
        $output->guestUserId = $entity->getGuestUserId()?->toRfc4122();
        $output->checkIn = $entity->getDateRange()->checkIn()->format(\DateTimeInterface::ATOM);
        $output->checkOut = $entity->getDateRange()->checkOut()->format(\DateTimeInterface::ATOM);
        $output->guestName = $entity->getGuestName()->toString();
        $output->status = $entity->getStatus()->value;
        $output->totalPrice = $entity->getPrice()->totalPrice;
        $output->pricePerNight = $entity->getPrice()->pricePerNight;
        $output->appliedDiscountPercentage = $entity->getPrice()->appliedDiscountPercentage;

        return $output;
    }
}
