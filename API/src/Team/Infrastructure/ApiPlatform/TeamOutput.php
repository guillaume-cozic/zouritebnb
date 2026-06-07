<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\ApiPlatform;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\OpenApi\Model\Example;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\ApiPlatform\State\FromEntityInterface;
use App\Team\Infrastructure\Doctrine\TeamEntity;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'TeamEntity',
    operations: [
        new Get(
            uriTemplate: '/teams/{id}',
            openapi: new OpenApiOperation(
                summary: 'Récupérer une équipe',
                description: 'Retourne une équipe et son projet solidaire coup de cœur. Réservé aux membres de l\'équipe.',
            ),
            normalizationContext: ['groups' => ['team:read']],
            security: 'is_authenticated()',
            provider: TeamItemProvider::class,
        ),
        new Patch(
            uriTemplate: '/teams/{id}/favorite-solidarity-project',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Définir le projet solidaire coup de cœur de l\'équipe',
                description: 'Associe un projet solidaire "coup de cœur" à l\'équipe. Envoyer null pour retirer la sélection.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/merge-patch+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: ['favoriteSolidarityProjectId' => '019cf27a-96ba-7957-8622-eeccb764b67f'],
                                ),
                                'clear' => new Example(
                                    summary: 'Retirer la sélection',
                                    value: ['favoriteSolidarityProjectId' => null],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            denormalizationContext: ['groups' => ['team:write']],
            security: 'is_authenticated()',
            input: UpdateTeamFavoriteSolidarityProjectInput::class,
            output: false,
            processor: UpdateTeamFavoriteSolidarityProjectProcessor::class,
        ),
        new Patch(
            uriTemplate: '/teams/{id}/bank-account',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Mettre à jour le compte bancaire de l\'équipe',
                description: 'Enregistre ou met à jour l\'IBAN, le BIC (optionnel) et le nom du titulaire utilisés pour recevoir les virements. Envoyer un iban vide ou null pour retirer le compte.',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/merge-patch+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: [
                                        'iban' => 'FR7630001007941234567890185',
                                        'bic' => 'BDFEFRPPCCT',
                                        'holderName' => 'Marie Hôte',
                                    ],
                                ),
                                'clear' => new Example(
                                    summary: 'Retirer le compte bancaire',
                                    value: ['iban' => null],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            denormalizationContext: ['groups' => ['team:write-bank-account']],
            security: 'is_authenticated()',
            input: UpdateTeamBankAccountInput::class,
            output: false,
            processor: UpdateTeamBankAccountProcessor::class,
        ),
    ],
    stateOptions: new Options(entityClass: TeamEntity::class),
)]
class TeamOutput implements FromEntityInterface
{
    #[Groups(['team:read'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'équipe', example: '019cf27a-96ba-7957-8622-eeccb7350e79')]
    public ?string $id = null;

    #[Groups(['team:read'])]
    #[ApiProperty(description: 'Identifiant UUID du projet solidaire coup de cœur', example: '019cf27a-96ba-7957-8622-eeccb764b67f')]
    public ?string $favoriteSolidarityProjectId = null;

    #[Groups(['team:read'])]
    #[ApiProperty(description: 'IBAN du compte bancaire utilisé pour les virements', example: 'FR7630001007941234567890185')]
    public ?string $iban = null;

    #[Groups(['team:read'])]
    #[ApiProperty(description: 'BIC du compte bancaire (optionnel)', example: 'BDFEFRPPCCT')]
    public ?string $bic = null;

    #[Groups(['team:read'])]
    #[ApiProperty(description: 'Nom du titulaire du compte bancaire', example: 'Marie Hôte')]
    public ?string $bankAccountHolderName = null;

    public static function fromEntity(object $entity): static
    {
        /** @var TeamEntity $entity */
        $output = new static();
        $output->id = $entity->getId()?->toRfc4122();
        $output->favoriteSolidarityProjectId = $entity->getFavoriteSolidarityProjectId()?->toRfc4122();
        $output->iban = $entity->getIban();
        $output->bic = $entity->getBic();
        $output->bankAccountHolderName = $entity->getBankAccountHolderName();

        return $output;
    }
}
