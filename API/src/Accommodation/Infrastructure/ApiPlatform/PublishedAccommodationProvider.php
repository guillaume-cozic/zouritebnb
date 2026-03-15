<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * @implements ProviderInterface<AccommodationOutput>
 */
final readonly class PublishedAccommodationProvider implements ProviderInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        $page = (int) ($context['filters']['page'] ?? 1);
        $itemsPerPage = 30;
        $offset = ($page - 1) * $itemsPerPage;

        $sql = <<<'SQL'
            SELECT
                BIN_TO_UUID(a.id) AS id,
                a.title,
                a.price,
                a.city,
                a.country,
                a.max_guests,
                a.status,
                (
                    SELECT p.filename
                    FROM accommodation_photo p
                    WHERE p.accommodation_id = a.id
                    LIMIT 1
                ) AS thumbnail_filename
            FROM accommodation a
            WHERE a.status = 'published'
            ORDER BY a.title ASC
            LIMIT :limit OFFSET :offset
            SQL;

        $rows = $this->connection->executeQuery($sql, [
            'limit' => $itemsPerPage,
            'offset' => $offset,
        ], [
            'limit' => ParameterType::INTEGER,
            'offset' => ParameterType::INTEGER,
        ])->fetchAllAssociative();

        $countSql = "SELECT COUNT(*) FROM accommodation WHERE status = 'published'";
        $totalItems = (int) $this->connection->executeQuery($countSql)->fetchOne();

        $outputs = [];
        foreach ($rows as $row) {
            $output = new AccommodationOutput();
            $output->id = $row['id'];
            $output->title = $row['title'];
            $output->price = null !== $row['price'] ? (float) $row['price'] : null;
            $output->city = $row['city'];
            $output->country = $row['country'];
            $output->maxGuests = null !== $row['max_guests'] ? (int) $row['max_guests'] : null;
            $output->status = $row['status'];
            $output->thumbnailUrl = null !== $row['thumbnail_filename']
                ? '/uploads/photos/'.$row['thumbnail_filename']
                : null;
            $outputs[] = $output;
        }

        return new TraversablePaginator(
            new \ArrayIterator($outputs),
            (float) $page,
            (float) $itemsPerPage,
            (float) $totalItems,
        );
    }
}
