<?php

declare(strict_types=1);

namespace App\Tests\E2e\SolidarityProject;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\SolidarityProject\Infrastructure\Doctrine\SolidarityProjectEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

abstract class SolidarityProjectApiTestCase extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    /**
     * @param array<array{value: string, label: string}>                                                                        $keyFigures   key figures for the default locale (fr)
     * @param array<string, array{title: string, description: string, keyFigures?: array<array{value: string, label: string}>}> $translations extra locales (e.g. "en"); the default locale (fr) is built from the flat arguments
     */
    protected function insertSolidarityProject(
        string $title,
        string $description,
        ?string $imageUrl = null,
        string $status = 'active',
        ?\DateTimeImmutable $createdAt = null,
        array $keyFigures = [],
        array $translations = [],
    ): string {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $allTranslations = [
            'fr' => ['title' => $title, 'description' => $description, 'keyFigures' => $keyFigures],
        ];
        foreach ($translations as $locale => $translation) {
            if ('fr' === $locale) {
                continue;
            }
            $allTranslations[$locale] = [
                'title' => $translation['title'],
                'description' => $translation['description'],
                'keyFigures' => $translation['keyFigures'] ?? [],
            ];
        }

        $entity = new SolidarityProjectEntity()
            ->setId(Uuid::v7())
            ->setImageUrl($imageUrl)
            ->setStatus($status)
            ->setCreatedAt($createdAt ?? new \DateTimeImmutable())
            ->setTranslations($allTranslations);

        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }
}
