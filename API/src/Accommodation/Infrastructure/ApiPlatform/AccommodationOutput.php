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
        ),
        new GetCollection(
            uriTemplate: '/accommodations',
            openapi: new OpenApiOperation(
                summary: 'Lister les hébergements',
                description: 'Retourne la liste paginée de tous les hébergements disponibles.',
            ),
            normalizationContext: ['groups' => ['accommodation:list']],
        ),
        new Patch(
            uriTemplate: '/accommodations/{id}/publish',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Publier un hébergement',
                description: 'Publie un hébergement pour le rendre visible dans le moteur de recherche.',
            ),
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
            denormalizationContext: ['groups' => ['accommodation:write']],
            input: UpdateAccommodationPriceInput::class,
            output: false,
            processor: UpdateAccommodationPriceProcessor::class,
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
            denormalizationContext: ['groups' => ['accommodation:write']],
            input: UpdateAccommodationAddressInput::class,
            output: false,
            processor: UpdateAccommodationAddressProcessor::class,
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
            denormalizationContext: ['groups' => ['accommodation:write']],
            input: UpdateAccommodationGeolocationInput::class,
            output: false,
            processor: UpdateAccommodationGeolocationProcessor::class,
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
            input: false,
            output: false,
            processor: DeleteAccommodationPhotoProcessor::class,
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

    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Description détaillée', example: 'Un chalet chaleureux au pied des pistes...')]
    public ?string $description = null;

    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Prix par nuit en euros', example: 150.0)]
    public ?float $price = null;

    #[Groups(['accommodation:read', 'accommodation:list'])]
    #[ApiProperty(description: 'Statut de publication', example: 'draft')]
    public ?string $status = null;

    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Rue', example: '12 rue de la Paix')]
    public ?string $street = null;

    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Ville', example: 'Paris')]
    public ?string $city = null;

    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Code postal', example: '75002')]
    public ?string $zipCode = null;

    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Pays', example: 'France')]
    public ?string $country = null;

    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Latitude GPS', example: 48.8566)]
    public ?float $latitude = null;

    #[Groups(['accommodation:read'])]
    #[ApiProperty(description: 'Longitude GPS', example: 2.3522)]
    public ?float $longitude = null;

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

        return $output;
    }
}
