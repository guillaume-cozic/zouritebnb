<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Example;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\RequestBody;

#[ApiResource(
    shortName: 'Review',
    operations: [
        new Post(
            uriTemplate: '/reviews/accommodation',
            status: 201,
            openapi: new OpenApiOperation(
                summary: 'Noter un hébergement (voyageur)',
                description: 'Permet à un voyageur connecté de noter l\'hébergement où il a séjourné. '
                    .'L\'auteur (authorUserId) est l\'utilisateur courant authentifié et doit avoir un séjour confirmé '
                    .'dont la date de départ est passée. La note doit être un entier entre 1 et 5, et le commentaire '
                    .'doit contenir au moins 50 caractères. '
                    .'Retourne 401 si aucun utilisateur authentifié n\'est fourni, '
                    .'422 si les règles métier sont violées (note hors bornes, commentaire trop court, séjour non terminé, avis déjà déposé).',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: [
                                        'authorUserId' => '01961e2f-dead-7000-beef-0000000000c1',
                                        'accommodationId' => '01961e2f-dead-7000-beef-000000000001',
                                        'rating' => 5,
                                        'comment' => 'Séjour vraiment agréable, logement propre, bien situé et hôte très réactif. Je recommande sans hésiter.',
                                    ],
                                ),
                                'comment_too_short' => new Example(
                                    summary: 'Invalide : commentaire trop court',
                                    description: 'Retourne 422 car le commentaire fait moins de 50 caractères.',
                                    value: [
                                        'authorUserId' => '01961e2f-dead-7000-beef-0000000000c1',
                                        'accommodationId' => '01961e2f-dead-7000-beef-000000000001',
                                        'rating' => 5,
                                        'comment' => 'Très bien.',
                                    ],
                                ),
                                'rating_out_of_bounds' => new Example(
                                    summary: 'Invalide : note hors bornes',
                                    description: 'Retourne 422 car la note doit être comprise entre 1 et 5.',
                                    value: [
                                        'authorUserId' => '01961e2f-dead-7000-beef-0000000000c1',
                                        'accommodationId' => '01961e2f-dead-7000-beef-000000000001',
                                        'rating' => 7,
                                        'comment' => 'Séjour vraiment agréable, logement propre, bien situé et hôte très réactif. Je recommande sans hésiter.',
                                    ],
                                ),
                                'stay_not_completed' => new Example(
                                    summary: 'Invalide : séjour non terminé',
                                    description: 'Retourne 422 car aucun séjour confirmé et terminé ne correspond à ce voyageur et cet hébergement.',
                                    value: [
                                        'authorUserId' => '01961e2f-dead-7000-beef-0000000000c2',
                                        'accommodationId' => '01961e2f-dead-7000-beef-000000000099',
                                        'rating' => 4,
                                        'comment' => 'Séjour correct dans l\'ensemble, je reviendrais probablement lors d\'un prochain passage dans la région.',
                                    ],
                                ),
                                'unauthenticated' => new Example(
                                    summary: 'Invalide : utilisateur non authentifié',
                                    description: 'Retourne 401 car aucun utilisateur authentifié (authorUserId) n\'est fourni.',
                                    value: [
                                        'authorUserId' => '',
                                        'accommodationId' => '01961e2f-dead-7000-beef-000000000001',
                                        'rating' => 5,
                                        'comment' => 'Séjour vraiment agréable, logement propre, bien situé et hôte très réactif. Je recommande sans hésiter.',
                                    ],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            denormalizationContext: ['groups' => ['review:write']],
            input: SubmitAccommodationReviewInput::class,
            processor: SubmitAccommodationReviewProcessor::class,
            read: false,
            output: false,
        ),
        new Post(
            uriTemplate: '/reviews/guest',
            status: 201,
            openapi: new OpenApiOperation(
                summary: 'Noter un voyageur (loueur)',
                description: 'Permet à un loueur connecté, membre de l\'équipe hôte de l\'hébergement, de noter un voyageur '
                    .'ayant effectué un séjour terminé. L\'auteur (authorUserId) est l\'utilisateur courant authentifié. '
                    .'La note doit être un entier entre 1 et 5, et le commentaire doit contenir au moins 50 caractères. '
                    .'Retourne 401 si aucun utilisateur authentifié n\'est fourni, '
                    .'403 si l\'auteur n\'est pas membre de l\'équipe hôte de l\'hébergement, '
                    .'422 si les règles métier sont violées (note hors bornes, commentaire trop court, séjour non terminé, avis déjà déposé).',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: [
                                        'authorUserId' => '01961e2f-dead-7000-beef-0000000000a1',
                                        'accommodationId' => '01961e2f-dead-7000-beef-000000000001',
                                        'guestUserId' => '01961e2f-dead-7000-beef-0000000000c1',
                                        'rating' => 5,
                                        'comment' => 'Voyageur exemplaire : communication parfaite, logement laissé impeccable et respect total du règlement intérieur.',
                                    ],
                                ),
                                'comment_too_short' => new Example(
                                    summary: 'Invalide : commentaire trop court',
                                    description: 'Retourne 422 car le commentaire fait moins de 50 caractères.',
                                    value: [
                                        'authorUserId' => '01961e2f-dead-7000-beef-0000000000a1',
                                        'accommodationId' => '01961e2f-dead-7000-beef-000000000001',
                                        'guestUserId' => '01961e2f-dead-7000-beef-0000000000c1',
                                        'rating' => 5,
                                        'comment' => 'Parfait.',
                                    ],
                                ),
                                'rating_out_of_bounds' => new Example(
                                    summary: 'Invalide : note hors bornes',
                                    description: 'Retourne 422 car la note doit être comprise entre 1 et 5.',
                                    value: [
                                        'authorUserId' => '01961e2f-dead-7000-beef-0000000000a1',
                                        'accommodationId' => '01961e2f-dead-7000-beef-000000000001',
                                        'guestUserId' => '01961e2f-dead-7000-beef-0000000000c1',
                                        'rating' => 0,
                                        'comment' => 'Voyageur exemplaire : communication parfaite, logement laissé impeccable et respect total du règlement intérieur.',
                                    ],
                                ),
                                'not_in_host_team' => new Example(
                                    summary: 'Invalide : auteur hors équipe hôte',
                                    description: 'Retourne 403 car l\'auteur n\'est pas membre de l\'équipe hôte de l\'hébergement.',
                                    value: [
                                        'authorUserId' => '01961e2f-dead-7000-beef-0000000000b9',
                                        'accommodationId' => '01961e2f-dead-7000-beef-000000000001',
                                        'guestUserId' => '01961e2f-dead-7000-beef-0000000000c1',
                                        'rating' => 5,
                                        'comment' => 'Voyageur exemplaire : communication parfaite, logement laissé impeccable et respect total du règlement intérieur.',
                                    ],
                                ),
                                'unauthenticated' => new Example(
                                    summary: 'Invalide : utilisateur non authentifié',
                                    description: 'Retourne 401 car aucun utilisateur authentifié (authorUserId) n\'est fourni.',
                                    value: [
                                        'authorUserId' => '',
                                        'accommodationId' => '01961e2f-dead-7000-beef-000000000001',
                                        'guestUserId' => '01961e2f-dead-7000-beef-0000000000c1',
                                        'rating' => 5,
                                        'comment' => 'Voyageur exemplaire : communication parfaite, logement laissé impeccable et respect total du règlement intérieur.',
                                    ],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            denormalizationContext: ['groups' => ['review:write']],
            input: SubmitGuestReviewInput::class,
            processor: SubmitGuestReviewProcessor::class,
            read: false,
            output: false,
        ),
    ],
)]
final class ReviewOutput
{
}
