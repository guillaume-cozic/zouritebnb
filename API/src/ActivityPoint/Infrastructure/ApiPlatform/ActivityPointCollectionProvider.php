<?php

declare(strict_types=1);

namespace App\ActivityPoint\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\DBAL\Connection;

/**
 * Public list of every activity point of the island, ordered by name, used by
 * the interactive map. No pagination: the whole set is small and the map needs
 * every point at once.
 *
 * @implements ProviderInterface<ActivityPointOutput>
 */
final readonly class ActivityPointCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return list<ActivityPointOutput>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $rows = $this->connection->executeQuery(
            <<<'SQL'
                SELECT
                    BIN_TO_UUID(p.id) AS id,
                    p.name,
                    p.description,
                    p.category,
                    p.latitude,
                    p.longitude,
                    p.article_url
                FROM activity_point p
                ORDER BY p.name ASC
                SQL,
        )->fetchAllAssociative();

        $points = [];
        foreach ($rows as $row) {
            $output = new ActivityPointOutput();
            $output->id = $row['id'];
            $output->name = $row['name'];
            $output->description = $row['description'];
            $output->category = $row['category'];
            $output->latitude = (float) $row['latitude'];
            $output->longitude = (float) $row['longitude'];
            $output->articleUrl = $row['article_url'];
            $points[] = $output;
        }

        return $points;
    }
}
