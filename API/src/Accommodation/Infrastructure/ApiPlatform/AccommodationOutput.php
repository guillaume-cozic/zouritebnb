<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Example;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\Shared\ApiPlatform\State\EntityProvider;
use App\Shared\ApiPlatform\State\FromEntityInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AccommodationEntity',
    operations: [
        new Get(
            uriTemplate: '/accommodations/{id}',
            openapi: new OpenApiOperation(
                summary: 'Récupérer un hébergement',
                description: 'Retourne le détail complet d\'un hébergement par son identifiant UUID.',
            ),
            normalizationContext: ['groups' => ['accommodation:read']],
            provider: AccommodationItemProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/accommodations',
            openapi: new OpenApiOperation(
                summary: 'Lister les hébergements publiés',
                description: 'Retourne la liste paginée des hébergements publiés avec leur photo principale.',
            ),
            normalizationContext: ['groups' => ['accommodation:list']],
            provider: PublishedAccommodationProvider::class,
        ),
        new Patch(
            uriTemplate: '/accommodations/{id}/publish',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Publier un hébergement',
                description: 'Publie un hébergement pour le rendre visible dans le moteur de recherche.',
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            input: false,
            output: false,
            processor: PublishAccommodationProcessor::class,
        ),
        new Patch(
            uriTemplate: '/accommodations/{id}/unpublish',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Dépublier un hébergement',
                description: 'Dépublie un hébergement pour le retirer du moteur de recherche.',
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            input: false,
            output: false,
            processor: UnpublishAccommodationProcessor::class,
        ),
        new Patch(
            uriTemplate: '/accommodations/{id}/price',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Modifier le prix d\'un hébergement',
                description: 'Met à jour le prix d\'un hébergement. Le prix doit être strictement positif (> 0).',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/merge-patch+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: ['price' => 200.0],
                                ),
                                'negative_price' => new Example(
                                    summary: 'Invalide : prix négatif',
                                    description: 'Retourne une erreur 422 car le prix doit être strictement positif.',
                                    value: ['price' => -50.0],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            denormalizationContext: ['groups' => ['accommodation:write']],
            input: UpdateAccommodationPriceInput::class,
            output: false,
            processor: UpdateAccommodationPriceProcessor::class,
        ),
        new Patch(
            uriTemplate: '/accommodations/{id}/weekly-promotion',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Modifier la promotion hebdomadaire d\'un hébergement',
                description: 'Met à jour le pourcentage de réduction appliqué aux séjours d\'au moins 7 nuits. La valeur doit être strictement supérieure à 0 et inférieure ou égale à 100. Envoyer null pour désactiver la promotion.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/merge-patch+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: ['weeklyPromotionPercentage' => 10.0],
                                ),
                                'disable' => new Example(
                                    summary: 'Désactiver la promotion',
                                    value: ['weeklyPromotionPercentage' => null],
                                ),
                                'out_of_bounds' => new Example(
                                    summary: 'Invalide : pourcentage hors bornes',
                                    description: 'Retourne une erreur 422 car la valeur doit être dans ]0, 100].',
                                    value: ['weeklyPromotionPercentage' => 150.0],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            denormalizationContext: ['groups' => ['accommodation:write']],
            input: UpdateAccommodationWeeklyPromotionInput::class,
            output: false,
            processor: UpdateAccommodationWeeklyPromotionProcessor::class,
        ),
        new Put(
            uriTemplate: '/accommodations/{id}/address',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Définir l\'adresse d\'un hébergement',
                description: 'Définit ou remplace l\'adresse complète d\'un hébergement. Tous les champs sont obligatoires.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: ['street' => '12 rue de la Paix', 'city' => 'Paris', 'zipCode' => '75002', 'country' => 'France'],
                                ),
                                'missing_street' => new Example(
                                    summary: 'Invalide : rue manquante',
                                    description: 'Retourne une erreur 422 car la rue est obligatoire.',
                                    value: ['city' => 'Paris', 'zipCode' => '75002', 'country' => 'France'],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            denormalizationContext: ['groups' => ['accommodation:write']],
            input: UpdateAccommodationAddressInput::class,
            output: false,
            processor: UpdateAccommodationAddressProcessor::class,
        ),
        new Put(
            uriTemplate: '/accommodations/{id}/capacity',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Définir la capacité d\'un hébergement',
                description: 'Définit ou remplace la capacité d\'un hébergement (chambres, salles de bain, voyageurs, lits).',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: ['bedrooms' => 3, 'bathrooms' => 2, 'maxGuests' => 6, 'singleBeds' => 2, 'doubleBeds' => 2],
                                ),
                                'negative_value' => new Example(
                                    summary: 'Invalide : valeur négative',
                                    description: 'Retourne une erreur 422 car les valeurs doivent être >= 0.',
                                    value: ['bedrooms' => -1, 'bathrooms' => 2, 'maxGuests' => 6, 'singleBeds' => 2, 'doubleBeds' => 2],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            denormalizationContext: ['groups' => ['accommodation:write']],
            input: UpdateAccommodationCapacityInput::class,
            output: false,
            processor: UpdateAccommodationCapacityProcessor::class,
        ),
        new Put(
            uriTemplate: '/accommodations/{id}/amenities',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Définir les équipements d\'un hébergement',
                description: 'Définit ou remplace la liste des équipements d\'un hébergement.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: ['codes' => ['private_pool', 'wifi', 'parking']],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            denormalizationContext: ['groups' => ['accommodation:write']],
            input: UpdateAccommodationAmenitiesInput::class,
            output: false,
            processor: UpdateAccommodationAmenitiesProcessor::class,
        ),
        new Put(
            uriTemplate: '/accommodations/{id}/geolocation',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Définir la géolocalisation d\'un hébergement',
                description: 'Définit ou remplace les coordonnées GPS d\'un hébergement.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: ['latitude' => 48.8566, 'longitude' => 2.3522],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            denormalizationContext: ['groups' => ['accommodation:write']],
            input: UpdateAccommodationGeolocationInput::class,
            output: false,
            processor: UpdateAccommodationGeolocationProcessor::class,
        ),
        new Put(
            uriTemplate: '/accommodations/{id}/check-in-out',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Définir les heures d\'arrivée et de départ',
                description: 'Définit les heures de check-in et check-out d\'un hébergement. Format HH:MM.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: ['checkIn' => '16:00', 'checkOut' => '12:00'],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            denormalizationContext: ['groups' => ['accommodation:write']],
            input: UpdateAccommodationCheckInOutInput::class,
            output: false,
            processor: UpdateAccommodationCheckInOutProcessor::class,
        ),
        new Put(
            uriTemplate: '/accommodations/{id}/description',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Modifier le titre et la description d\'un hébergement',
                description: 'Met à jour le titre et la description d\'un hébergement.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: ['title' => 'Mon hébergement', 'description' => 'Une description détaillée...'],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            denormalizationContext: ['groups' => ['accommodation:write']],
            input: UpdateAccommodationDescriptionInput::class,
            output: false,
            processor: UpdateAccommodationDescriptionProcessor::class,
        ),
        new Post(
            uriTemplate: '/accommodations',
            status: 201,
            openapi: new OpenApiOperation(
                summary: 'Créer un hébergement',
                description: 'Crée un nouvel hébergement. Le prix doit être strictement positif (> 0).',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: ['title' => 'Chalet Montagne', 'description' => 'Un chalet chaleureux au pied des pistes...', 'price' => 150.0],
                                ),
                                'missing_price' => new Example(
                                    summary: 'Invalide : prix manquant',
                                    description: 'Retourne une erreur 422 car le prix est requis.',
                                    value: ['title' => 'Chalet Montagne', 'description' => 'Un chalet...'],
                                ),
                                'negative_price' => new Example(
                                    summary: 'Invalide : prix négatif',
                                    description: 'Retourne une erreur 422 car le prix doit être strictement positif.',
                                    value: ['title' => 'Chalet Montagne', 'description' => 'Un chalet...', 'price' => -50.0],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            denormalizationContext: ['groups' => ['accommodation:write']],
            input: AccommodationInput::class,
            processor: CreateAccommodationProcessor::class,
        ),
        new Post(
            uriTemplate: '/accommodations/{id}/photos',
            status: 201,
            openapi: new OpenApiOperation(
                summary: 'Ajouter une photo à un hébergement',
                description: 'Upload une photo pour un hébergement. Formats acceptés : JPEG, PNG, WebP. Maximum 20 photos par hébergement.',
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            inputFormats: ['multipart' => ['multipart/form-data']],
            deserialize: false,
            input: false,
            output: false,
            processor: UploadAccommodationPhotoProcessor::class,
        ),
        new Delete(
            uriTemplate: '/accommodations/{id}/photos/{photoId}',
            status: 204,
            read: false,
            openapi: new OpenApiOperation(
                summary: 'Supprimer une photo d\'un hébergement',
                description: 'Supprime une photo existante d\'un hébergement par son identifiant.',
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            input: false,
            output: false,
            processor: DeleteAccommodationPhotoProcessor::class,
        ),
        new Put(
            uriTemplate: '/accommodations/{id}/photos/reorder',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Réordonner les photos d\'un hébergement',
                description: 'Remplace l\'ordre des photos d\'un hébergement. Envoyer la liste complète des IDs de photos dans l\'ordre souhaité.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: ['photoIds' => ['uuid-1', 'uuid-2', 'uuid-3']],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            denormalizationContext: ['groups' => ['accommodation:write']],
            input: ReorderAccommodationPhotosInput::class,
            output: false,
            processor: ReorderAccommodationPhotosProcessor::class,
        ),
    ],
    provider: EntityProvider::class,
    stateOptions: new Options(entityClass: AccommodationEntity::class),
)]
class AccommodationOutput implements FromEntityInterface
{
    #[Groups(['accommodation:read', 'accommodation:list'])]
    #[ApiProperty(description: 'Identifiant unique (UUID)', example: '01961e2f-dead-7000-beef-000000000001')]
    public ?string $id = null;

    #[Groups(['accommodation:read', 'accommodation:list'])]
    #[ApiProperty(description: 'Nom de l\'hébergement', example: 'Chalet Montagne')]
    public ?string $title = null;

    #[Groups(['accommodation:read', 'accommodation:list'])]
    #[ApiProperty(description: 'Description détaillée', example: 'Un chalet chaleureux au pied des pistes...')]
    public ?string $description = null;

    #[Groups(['accommodation:read', 'accommodation:list'])]
    #[ApiProperty(description: 'Prix par nuit en euros', example: 150.0)]
    public ?float $price = null;

    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Pourcentage de réduction appliqué aux séjours d\'au moins 7 nuits', example: 10.0)]
    public ?float $weeklyPromotionPercentage = null;

    #[Groups(['accommodation:read', 'accommodation:list'])]
    #[ApiProperty(description: 'Statut de publication', example: 'draft')]
    public ?string $status = null;

    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Rue', example: '12 rue de la Paix')]
    public ?string $street = null;

    #[Groups(['accommodation:read', 'accommodation:list'])]
    #[ApiProperty(description: 'Ville', example: 'Paris')]
    public ?string $city = null;

    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Code postal', example: '75002')]
    public ?string $zipCode = null;

    #[Groups(['accommodation:read', 'accommodation:list'])]
    #[ApiProperty(description: 'Pays', example: 'France')]
    public ?string $country = null;

    #[Groups(['accommodation:read', 'accommodation:list'])]
    #[ApiProperty(description: 'Latitude GPS', example: 48.8566)]
    public ?float $latitude = null;

    #[Groups(['accommodation:read', 'accommodation:list'])]
    #[ApiProperty(description: 'Longitude GPS', example: 2.3522)]
    public ?float $longitude = null;

    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Nombre de chambres', example: 3)]
    public ?int $bedrooms = null;

    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Nombre de salles de bain', example: 2)]
    public ?int $bathrooms = null;

    #[Groups(['accommodation:read', 'accommodation:list'])]
    #[ApiProperty(description: 'Nombre maximum de voyageurs', example: 6)]
    public ?int $maxGuests = null;

    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Nombre de lits simples', example: 2)]
    public ?int $singleBeds = null;

    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Nombre de lits doubles', example: 2)]
    public ?int $doubleBeds = null;

    #[Groups(['accommodation:read', 'accommodation:list'])]
    #[ApiProperty(description: 'Liste des codes d\'équipements', example: ['private_pool', 'wifi', 'parking'])]
    public ?array $amenities = null;

    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Heure d\'arrivée', example: '16:00')]
    public ?string $checkIn = null;

    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Heure de départ', example: '12:00')]
    public ?string $checkOut = null;

    #[Groups(['accommodation:read', 'accommodation:list'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'équipe propriétaire', example: '019cf27a-96ba-7957-8622-eeccb7350e79')]
    public ?string $teamId = null;

    #[Groups(['accommodation:list'])]
    #[ApiProperty(description: 'URL de la photo principale', example: '/uploads/photos/abc123.jpg')]
    public ?string $thumbnailUrl = null;

    /** @var string[] */
    #[Groups(['accommodation:list'])]
    #[ApiProperty(description: 'URLs ordonnées de toutes les photos de l\'hébergement (utilisées pour le carrousel de la carte de liste)', example: ['/uploads/photos/abc123.jpg', '/uploads/photos/def456.jpg'])]
    public array $photoUrls = [];

    /** @var array<array{id: string, url: string}>|null */
    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Liste des photos de l\'hébergement')]
    public ?array $photos = null;

    public static function fromEntity(object $entity): static
    {
        /** @var AccommodationEntity $entity */
        $output = new static();
        $output->id = $entity->getId()?->toRfc4122();
        $output->title = $entity->getTitle();
        $output->description = $entity->getDescription();
        $output->price = $entity->getPrice();
        $output->status = $entity->getStatus();
        $output->street = $entity->getStreet();
        $output->city = $entity->getCity();
        $output->zipCode = $entity->getZipCode();
        $output->country = $entity->getCountry();
        $output->latitude = $entity->getLatitude();
        $output->longitude = $entity->getLongitude();
        $output->bedrooms = $entity->getBedrooms();
        $output->bathrooms = $entity->getBathrooms();
        $output->maxGuests = $entity->getMaxGuests();
        $output->singleBeds = $entity->getSingleBeds();
        $output->doubleBeds = $entity->getDoubleBeds();
        $output->amenities = $entity->getAmenities();
        $output->checkIn = $entity->getCheckIn();
        $output->checkOut = $entity->getCheckOut();
        $output->teamId = $entity->getTeamId()?->toRfc4122();
        $output->weeklyPromotionPercentage = $entity->getWeeklyPromotionPercentage();

        return $output;
    }
}
