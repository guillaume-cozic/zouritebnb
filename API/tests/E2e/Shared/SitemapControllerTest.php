<?php

declare(strict_types=1);

namespace App\Tests\E2e\Shared;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\SolidarityProject\Infrastructure\Doctrine\SolidarityProjectEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class SitemapControllerTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function test_should_list_static_pages_published_accommodations_and_active_projects(): void
    {
        $published = $this->insertAccommodation('published');
        $draft = $this->insertAccommodation('draft');
        $active = $this->insertSolidarityProject('active');
        $closed = $this->insertSolidarityProject('closed');

        $client = self::createClient();
        $client->request('GET', '/sitemap.xml');

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('content-type', 'application/xml; charset=UTF-8');

        $xml = $client->getResponse()->getContent();
        self::assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);

        $frontendUrl = $_ENV['FRONTEND_URL'] ?? $_SERVER['FRONTEND_URL'] ?? '';
        self::assertNotSame('', $frontendUrl);
        self::assertStringContainsString("<loc>{$frontendUrl}/</loc>", $xml);
        self::assertStringContainsString("<loc>{$frontendUrl}/accommodations</loc>", $xml);
        self::assertStringContainsString("<loc>{$frontendUrl}/solidarity-projects</loc>", $xml);
        self::assertStringContainsString("<loc>{$frontendUrl}/mentions-legales</loc>", $xml);

        self::assertStringContainsString("/accommodations/{$published}</loc>", $xml);
        self::assertStringNotContainsString($draft, $xml);
        self::assertStringContainsString("/solidarity-projects/{$active}</loc>", $xml);
        self::assertStringNotContainsString($closed, $xml);
    }

    private function insertAccommodation(string $status): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new AccommodationEntity()
            ->setId(Uuid::v7())
            ->setTitle('Villa '.$status)
            ->setDescription('A villa')
            ->setPrice(100.0)
            ->setStatus($status);

        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }

    private function insertSolidarityProject(string $status): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new SolidarityProjectEntity()
            ->setId(Uuid::v7())
            ->setStatus($status)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setTranslations(['fr' => ['title' => 'Projet '.$status, 'description' => 'Description']]);

        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }
}
