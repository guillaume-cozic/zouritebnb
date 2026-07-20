<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Sitemap XML du site public, servi par l'API et proxifié par le nginx du
 * front sous https://www.zouritebnb.com/sitemap.xml. Le blog Astro génère son
 * propre sitemap (/blog/sitemap-index.xml) ; les deux sont déclarés dans le
 * robots.txt du front.
 */
#[AsController]
final readonly class SitemapController
{
    private const array STATIC_PATHS = [
        '/',
        '/accommodations',
        '/solidarity-projects',
        '/cgu',
        '/cgv',
        '/mentions-legales',
        '/confidentialite',
    ];

    public function __construct(
        private Connection $connection,
        private string $frontendUrl,
    ) {
    }

    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function __invoke(): Response
    {
        $paths = self::STATIC_PATHS;

        // SQL brut plutôt que les repositories des modules : Shared ne doit
        // dépendre d'aucun module (règle phparkitect).
        foreach ($this->connection->fetchFirstColumn("SELECT BIN_TO_UUID(id) FROM accommodation WHERE status = 'published' ORDER BY id") as $id) {
            $paths[] = '/accommodations/'.$id;
        }

        foreach ($this->connection->fetchFirstColumn("SELECT BIN_TO_UUID(id) FROM solidarity_project WHERE status = 'active' ORDER BY id") as $id) {
            $paths[] = '/solidarity-projects/'.$id;
        }

        $urls = implode('', array_map(
            fn (string $path): string => \sprintf("  <url><loc>%s</loc></url>\n", htmlspecialchars($this->frontendUrl.$path, \ENT_XML1)),
            $paths,
        ));

        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
            {$urls}</urlset>
            XML;

        return new Response($xml, Response::HTTP_OK, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
