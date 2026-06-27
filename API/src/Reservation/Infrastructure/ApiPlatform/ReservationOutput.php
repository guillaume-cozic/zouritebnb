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
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            openapi: new OpenApiOperation(
                summary: 'Récupérer une réservation',
                description: 'Retourne le détail complet d\'une réservation par son identifiant UUID. Accessible uniquement au voyageur de la réservation ou à un membre de l\'équipe loueur (403 sinon). Retourne 404 si la réservation est introuvable.',
            ),
            normalizationContext: ['groups' => ['reservation:read']],
            provider: ReservationItemProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/reservations',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            openapi: new OpenApiOperation(
                summary: 'Lister les réservations',
                description: 'Retourne les réservations visibles par l\'utilisateur courant : celles de son équipe (en tant que loueur) et celles où il est le voyageur. Filtres optionnels : accommodationId (UUID de l\'hébergement), from et to (intervalle de dates ISO 8601 — les réservations qui chevauchent cet intervalle sont retournées).',
            ),
            normalizationContext: ['groups' => ['reservation:read']],
            provider: ReservationCollectionProvider::class,
        ),
        new Post(
            uriTemplate: '/reservations',
            status: 201,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            openapi: new OpenApiOperation(
                summary: 'Créer une réservation (back-office)',
                description: 'Crée une réservation directement en statut "confirmed" depuis le back-office hôte, sans étape d\'approbation (aucun loueur associé : guestUserId reste null). La réservation est rattachée à l\'équipe de l\'utilisateur authentifié. Pour le parcours public où un loueur soumet une demande à valider par l\'hôte, utiliser POST /reservations/request. La date de départ doit être strictement postérieure à la date d\'arrivée et le nom du voyageur ne peut pas être vide.',
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
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            openapi: new OpenApiOperation(
                summary: 'Confirmer une réservation',
                description: 'L\'équipe loueur passe une réservation du statut "pending" au statut "confirmed". Réservé aux membres de l\'équipe propriétaire (403 sinon). Retourne 404 si la réservation est introuvable, 422 si elle est déjà confirmée ou annulée.',
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
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            openapi: new OpenApiOperation(
                summary: 'Annuler une réservation',
                description: 'Passe une réservation au statut "cancelled". Accessible au voyageur de la réservation ou à un membre de l\'équipe loueur (403 sinon). Le séjour ne doit pas avoir commencé : une réservation en cours ou passée ne peut pas être annulée. Un message facultatif peut accompagner l\'annulation ; il est publié dans la conversation liée. Retourne 404 si la réservation est introuvable, 422 si elle est déjà annulée, refusée, ou si le séjour a déjà commencé. La réponse expose le montant remboursé (refundAmount, refundPercentage) selon la politique figée et la date courante.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/merge-patch+json' => new MediaType(
                            examples: new \ArrayObject([
                                'without_message' => new Example(
                                    summary: 'Annulation sans message',
                                    value: new \ArrayObject(),
                                ),
                                'with_message' => new Example(
                                    summary: 'Annulation avec message',
                                    value: ['message' => 'Un imprévu m\'oblige à annuler, désolé pour le dérangement.'],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            denormalizationContext: ['groups' => ['reservation:write']],
            normalizationContext: ['groups' => ['reservation:read']],
            input: CancelReservationInput::class,
            processor: CancelReservationProcessor::class,
        ),
        new Post(
            uriTemplate: '/reservations/request',
            status: 201,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            openapi: new OpenApiOperation(
                summary: 'Demander une réservation (parcours B2C)',
                description: 'Crée une demande de réservation en statut "pending" depuis le parcours public. Le voyageur est l\'utilisateur authentifié (déduit du JWT). Une conversation entre l\'hôte et le loueur est ouverte automatiquement. L\'hôte dispose de 24h pour accepter ou refuser. La date de départ doit être strictement postérieure à la date d\'arrivée.',
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
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            openapi: new OpenApiOperation(
                summary: 'Refuser une réservation (hôte)',
                description: 'L\'hôte refuse une réservation en statut "pending". Réservé aux membres de l\'équipe propriétaire (403 sinon). Passe au statut "refused". Retourne 404 si introuvable, 422 si la réservation n\'est pas en "pending".',
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
    #[ApiProperty(description: 'Nombre de voyageurs de la réservation', example: 2)]
    public ?int $guestCount = null;

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

    #[Groups(['reservation:read'])]
    #[ApiProperty(description: 'Montant total réglé par le voyageur : séjour + frais de service + don solidaire (identique au total de la facture)', example: 460.0)]
    public ?float $totalPaid = null;

    #[Groups(['reservation:read'])]
    #[ApiProperty(description: 'Politique d\'annulation figée à la réservation : "flexible" ou "moderate"', example: 'flexible')]
    public ?string $cancellationPolicy = null;

    #[Groups(['reservation:read'])]
    #[ApiProperty(description: 'Indique si la réservation peut être annulée maintenant (statut en attente/confirmé et séjour pas encore commencé)', example: true)]
    public bool $cancellable = false;

    #[Groups(['reservation:read'])]
    #[ApiProperty(description: 'Montant qui serait remboursé au voyageur en cas d\'annulation immédiate, selon la politique et la date courante', example: 460.0)]
    public ?float $refundAmount = null;

    #[Groups(['reservation:read'])]
    #[ApiProperty(description: 'Pourcentage remboursé en cas d\'annulation immédiate (0, 50 ou 100)', example: 100)]
    public ?int $refundPercentage = null;

    public static function fromEntity(object $entity, ?\DateTimeImmutable $now = null, bool $byHost = false): static
    {
        if (!$entity instanceof Reservation) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', Reservation::class, get_debug_type($entity)));
        }

        $output = new static();
        $output->id = $entity->getId()->toString();
        $output->accommodationId = $entity->getAccommodationId()->toRfc4122();
        $output->teamId = $entity->getTeamId()->toRfc4122();
        $output->guestUserId = $entity->getGuestUserId()?->toRfc4122();
        $output->checkIn = $entity->getDateRange()->checkIn()->format(\DateTimeInterface::ATOM);
        $output->checkOut = $entity->getDateRange()->checkOut()->format(\DateTimeInterface::ATOM);
        $output->guestName = $entity->getGuestName()->toString();
        $output->guestCount = $entity->getGuestCount()->value();
        $output->status = $entity->getStatus()->value;
        $price = $entity->getPrice();
        $output->totalPrice = $price->totalPrice;
        $output->pricePerNight = $price->pricePerNight;
        $output->appliedDiscountPercentage = $price->appliedDiscountPercentage;
        // Grand total actually paid, mirroring the invoice (stay + frozen fee + donation).
        $output->totalPaid = round($price->totalPrice + $price->commissionAmount + $price->donationAmount, 2);
        $output->cancellationPolicy = $entity->getCancellationPolicy()->value;

        // The refund preview depends on "now", so it is only computed when a clock is
        // provided (item/collection reads, cancel response); other flows leave it null.
        if (null !== $now) {
            $output->cancellable = $entity->isCancellable($now);
            // A host viewing the reservation sees the full-refund preview that
            // would apply if THEY cancelled; the guest sees the policy refund.
            $refund = $entity->refundBreakdown($now, $byHost);
            $output->refundAmount = $refund->refundAmount;
            $output->refundPercentage = $refund->refundPercentage;
        }

        return $output;
    }
}
