<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use App\SolidarityProject\Domain\Entity\SolidarityProject;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AdminSolidarityProjectInput
{
    /**
     * @param array<array{value: string, label: string}>                                                                          $keyFigures
     * @param array<string, array{title?: string, description?: string, keyFigures?: array<array{value: string, label: string}>}> $translations
     */
    public function __construct(
        #[Groups(['admin_solidarity_project:write'])]
        #[ApiProperty(description: 'Titre du projet dans la langue par défaut (fr)', example: 'Reforestation de l\'île Rodrigues')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title = '',

        #[Groups(['admin_solidarity_project:write'])]
        #[ApiProperty(description: 'Description du projet dans la langue par défaut (fr)', example: 'Plantation de 10 000 arbres endémiques sur trois ans.')]
        #[Assert\NotBlank]
        public string $description = '',

        #[Groups(['admin_solidarity_project:write'])]
        #[ApiProperty(description: 'URL de l\'image du projet (optionnelle)', example: 'https://example.com/images/project.jpg')]
        #[Assert\Url]
        #[Assert\Length(max: 2048)]
        public ?string $imageUrl = null,

        #[Groups(['admin_solidarity_project:write'])]
        #[ApiProperty(description: 'Statut initial du projet (active ou closed)', example: 'active')]
        #[Assert\Choice(choices: [SolidarityProject::STATUS_ACTIVE, SolidarityProject::STATUS_CLOSED])]
        public string $status = SolidarityProject::STATUS_ACTIVE,

        /**
         * @var array<array{value: string, label: string}>
         */
        #[Groups(['admin_solidarity_project:write'])]
        #[ApiProperty(
            description: 'Chiffres clés du projet (valeur + libellé)',
            example: [['value' => '10 000', 'label' => 'arbres plantés']],
        )]
        #[Assert\All([
            new Assert\Collection(
                fields: [
                    'value' => [new Assert\NotBlank(), new Assert\Type('string')],
                    'label' => [new Assert\NotBlank(), new Assert\Type('string')],
                ],
                allowExtraFields: false,
            ),
        ])]
        public array $keyFigures = [],

        /**
         * Translations into non-default locales (e.g. "en"). The default locale (fr)
         * is taken from the flat title/description/keyFigures fields above. A locale
         * present here must be fully translated (non-blank title and description).
         *
         * @var array<string, array{title?: string, description?: string, keyFigures?: array<array{value: string, label: string}>}>
         */
        #[Groups(['admin_solidarity_project:write'])]
        #[ApiProperty(
            description: 'Traductions du projet dans les langues autres que la langue par défaut (ex. "en"). La langue par défaut (fr) provient des champs title/description/keyFigures.',
            example: ['en' => ['title' => 'Reforesting Rodrigues', 'description' => 'Planting 10,000 endemic trees.', 'keyFigures' => [['value' => '10,000', 'label' => 'trees planted']]]],
        )]
        #[Assert\All([
            new Assert\Collection(
                fields: [
                    'title' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
                    'description' => [new Assert\NotBlank()],
                    'keyFigures' => new Assert\Optional([
                        new Assert\All([
                            new Assert\Collection(
                                fields: [
                                    'value' => [new Assert\NotBlank(), new Assert\Type('string')],
                                    'label' => [new Assert\NotBlank(), new Assert\Type('string')],
                                ],
                                allowExtraFields: false,
                            ),
                        ]),
                    ]),
                ],
                allowExtraFields: false,
            ),
        ])]
        public array $translations = [],
    ) {
    }

    /**
     * Assembles the full per-locale content map: the default locale (fr) from the
     * flat fields, plus every extra locale from {@see $translations}.
     *
     * @return array<string, array{title: string, description: string, keyFigures: array<array{value: string|null, label: string|null}>}>
     */
    public function toTranslations(): array
    {
        $translations = [
            SolidarityProject::DEFAULT_LOCALE => [
                'title' => $this->title,
                'description' => $this->description,
                'keyFigures' => $this->keyFigures,
            ],
        ];

        foreach ($this->translations as $locale => $translation) {
            // The flat fields are the single source of truth for the default locale.
            if (SolidarityProject::DEFAULT_LOCALE === $locale) {
                continue;
            }

            $translations[$locale] = [
                'title' => $translation['title'] ?? '',
                'description' => $translation['description'] ?? '',
                'keyFigures' => $translation['keyFigures'] ?? [],
            ];
        }

        return $translations;
    }
}
