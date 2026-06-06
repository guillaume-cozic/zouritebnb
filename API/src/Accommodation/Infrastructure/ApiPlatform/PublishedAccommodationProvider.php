<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<AccommodationOutput>
 */
final readonly class PublishedAccommodationProvider implements ProviderInterface
{
    public function __construct(
        private Connection $connection,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        $request = $this->requestStack->getCurrentRequest();
        $query = $request?->query;

        $page = (int) ($query?->get('page') ?? 1);
        $itemsPerPageRaw = (int) ($query?->get('itemsPerPage') ?? 30);
        $itemsPerPage = max(1, min(30, $itemsPerPageRaw));
        $offset = ($page - 1) * $itemsPerPage;

        $statusFilter = $query?->get('status') ?? 'published';
        $allowedStatuses = ['published', 'draft', 'all'];
        if (!\in_array($statusFilter, $allowedStatuses, true)) {
            $statusFilter = 'published';
        }

        $clauses = [];
        $params = [];
        $types = [];

        if ('all' !== $statusFilter) {
            $clauses[] = 'a.status = :status';
            $params['status'] = $statusFilter;
        }

        $cityRaw = $query?->get('city');
        if (\is_string($cityRaw) && '' !== trim($cityRaw)) {
            $clauses[] = "REPLACE(LOWER(a.city), '-', ' ') LIKE REPLACE(LOWER(:city), '-', ' ')";
            $params['city'] = '%'.trim($cityRaw).'%';
        }

        $guestsRaw = $query?->get('guests');
        if (null !== $guestsRaw && '' !== $guestsRaw && (int) $guestsRaw > 0) {
            $clauses[] = '(a.max_guests IS NULL OR a.max_guests >= :guests)';
            $params['guests'] = (int) $guestsRaw;
            $types['guests'] = ParameterType::INTEGER;
        }

        $priceMinRaw = $query?->get('priceMin');
        if (null !== $priceMinRaw && '' !== $priceMinRaw && is_numeric($priceMinRaw)) {
            $clauses[] = 'a.price >= :priceMin';
            $params['priceMin'] = (float) $priceMinRaw;
        }

        $priceMaxRaw = $query?->get('priceMax');
        if (null !== $priceMaxRaw && '' !== $priceMaxRaw && is_numeric($priceMaxRaw)) {
            $clauses[] = 'a.price <= :priceMax';
            $params['priceMax'] = (float) $priceMaxRaw;
        }

        // amenities[] (or comma-separated) — accommodation must contain ALL of them
        $amenitiesRaw = $query?->all('amenities') ?? [];
        if (\is_string($amenitiesRaw)) {
            $amenitiesRaw = explode(',', $amenitiesRaw);
        }
        $amenities = array_values(array_filter(array_map(
            static fn ($v) => \is_string($v) ? trim($v) : '',
            (array) $amenitiesRaw
        ), static fn ($v) => '' !== $v));
        foreach ($amenities as $i => $code) {
            $paramName = 'amenity'.$i;
            $clauses[] = "JSON_CONTAINS(a.amenities, JSON_QUOTE(:{$paramName}))";
            $params[$paramName] = $code;
        }

        $whereSql = [] === $clauses ? '1=1' : implode(' AND ', $clauses);

        $sql = <<<SQL
            SELECT
                BIN_TO_UUID(a.id) AS id,
                a.title,
                a.description,
                a.price,
                a.city,
                a.country,
                a.latitude,
                a.longitude,
                a.max_guests,
                a.status,
                a.amenities,
                (
                    SELECT p.filename
                    FROM accommodation_photo p
                    WHERE p.accommodation_id = a.id
                    LIMIT 1
                ) AS thumbnail_filename
            FROM accommodation a
            WHERE {$whereSql}
            ORDER BY a.title ASC
            LIMIT :limit OFFSET :offset
            SQL;

        $dataParams = $params + [
            'limit' => $itemsPerPage,
            'offset' => $offset,
        ];
        $dataTypes = $types + [
            'limit' => ParameterType::INTEGER,
            'offset' => ParameterType::INTEGER,
        ];

        $rows = $this->connection->executeQuery($sql, $dataParams, $dataTypes)->fetchAllAssociative();

        $countSql = "SELECT COUNT(*) FROM accommodation a WHERE {$whereSql}";
        $totalItems = (int) $this->connection->executeQuery($countSql, $params, $types)->fetchOne();

        $photoUrlsByAccommodation = $this->loadPhotoUrlsByAccommodation(array_column($rows, 'id'));

        $outputs = [];
        foreach ($rows as $row) {
            $output = new AccommodationOutput();
            $output->id = $row['id'];
            $output->title = $row['title'];
            $output->description = $row['description'];
            $output->price = null !== $row['price'] ? (float) $row['price'] : null;
            $output->city = $row['city'];
            $output->country = $row['country'];
            $output->latitude = null !== $row['latitude'] ? (float) $row['latitude'] : null;
            $output->longitude = null !== $row['longitude'] ? (float) $row['longitude'] : null;
            $output->maxGuests = null !== $row['max_guests'] ? (int) $row['max_guests'] : null;
            $output->status = $row['status'];
            $output->amenities = null !== $row['amenities']
                ? (\is_array($decoded = json_decode((string) $row['amenities'], true)) ? $decoded : null)
                : null;
            $output->thumbnailUrl = null !== $row['thumbnail_filename']
                ? '/uploads/photos/'.$row['thumbnail_filename']
                : null;
            $output->photoUrls = $photoUrlsByAccommodation[$row['id']] ?? [];
            $outputs[] = $output;
        }

        return new TraversablePaginator(
            new \ArrayIterator($outputs),
            (float) $page,
            (float) $itemsPerPage,
            (float) $totalItems,
        );
    }

    /**
     * @param string[] $accommodationIds UUIDs as text
     *
     * @return array<string, string[]> map of accommodationId → ordered list of photo URLs
     */
    private function loadPhotoUrlsByAccommodation(array $accommodationIds): array
    {
        if ([] === $accommodationIds) {
            return [];
        }

        $binaryIds = array_map(static fn (string $id): string => hex2bin(str_replace('-', '', $id)), $accommodationIds);

        $sql = <<<SQL
            SELECT BIN_TO_UUID(p.accommodation_id) AS accommodation_id, p.filename
            FROM accommodation_photo p
            WHERE p.accommodation_id IN (?)
            ORDER BY p.accommodation_id, p.id
            SQL;

        $rows = $this->connection->executeQuery(
            $sql,
            [$binaryIds],
            [ArrayParameterType::BINARY],
        )->fetchAllAssociative();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['accommodation_id']][] = '/uploads/photos/'.$row['filename'];
        }

        return $map;
    }
}
