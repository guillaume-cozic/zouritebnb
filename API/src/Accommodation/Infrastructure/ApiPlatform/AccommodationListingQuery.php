<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\State\Pagination\TraversablePaginator;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * Shared SQL backbone of the accommodation listing providers (public catalog
 * and team-scoped back-office): pagination parsing, paginated query, row →
 * AccommodationOutput mapping and batched photo-URL loading.
 *
 * Providers keep only what makes them different: their filtering and security
 * clauses.
 */
final readonly class AccommodationListingQuery
{
    private const ALLOWED_STATUSES = ['published', 'draft', 'all'];

    public function __construct(private Connection $connection)
    {
    }

    public function pageFromQuery(?InputBag $query): int
    {
        return (int) ($query?->get('page') ?? 1);
    }

    public function itemsPerPageFromQuery(?InputBag $query): int
    {
        return max(1, min(30, (int) ($query?->get('itemsPerPage') ?? 30)));
    }

    public function statusFromQuery(?InputBag $query, string $default): string
    {
        $status = $query?->get('status') ?? $default;

        return \in_array($status, self::ALLOWED_STATUSES, true) ? $status : $default;
    }

    /**
     * @param string[]             $clauses
     * @param array<string, mixed> $params
     * @param array<string, mixed> $types
     */
    public function paginate(
        array $clauses,
        array $params,
        array $types,
        int $page,
        int $itemsPerPage,
        bool $withReviewStats,
    ): TraversablePaginator {
        $whereSql = [] === $clauses ? '1=1' : implode(' AND ', $clauses);

        $reviewColumns = $withReviewStats ? <<<SQL
            ,
                (
                    SELECT AVG(r.rating)
                    FROM review r
                    WHERE r.type = 'accommodation' AND r.subject_accommodation_id = a.id
                ) AS average_rating,
                (
                    SELECT COUNT(*)
                    FROM review r
                    WHERE r.type = 'accommodation' AND r.subject_accommodation_id = a.id
                ) AS review_count
            SQL : '';

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
                ) AS thumbnail_filename{$reviewColumns}
            FROM accommodation a
            WHERE {$whereSql}
            ORDER BY a.title ASC
            LIMIT :limit OFFSET :offset
            SQL;

        $dataParams = $params + [
            'limit' => $itemsPerPage,
            'offset' => ($page - 1) * $itemsPerPage,
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
            $outputs[] = $this->mapRow($row, $photoUrlsByAccommodation, $withReviewStats);
        }

        return new TraversablePaginator(
            new \ArrayIterator($outputs),
            (float) $page,
            (float) $itemsPerPage,
            (float) $totalItems,
        );
    }

    /**
     * @param array<string, mixed>    $row
     * @param array<string, string[]> $photoUrlsByAccommodation
     */
    private function mapRow(array $row, array $photoUrlsByAccommodation, bool $withReviewStats): AccommodationOutput
    {
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

        if ($withReviewStats) {
            $output->averageRating = isset($row['average_rating'])
                ? round((float) $row['average_rating'], 1)
                : null;
            $output->reviewCount = (int) ($row['review_count'] ?? 0);
        }

        return $output;
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
