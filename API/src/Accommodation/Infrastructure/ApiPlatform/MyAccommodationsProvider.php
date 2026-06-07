<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Shared\Infrastructure\Security\CurrentUser;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Back-office listing: returns ONLY the accommodations owned by the authenticated
 * user's team, regardless of their publication status.
 *
 * Unlike {@see PublishedAccommodationProvider} (the public, cross-team catalog used by
 * the homepage), this provider is team-scoped and authenticated so an owner never sees
 * another team's accommodations or drafts.
 *
 * @implements ProviderInterface<AccommodationOutput>
 */
final readonly class MyAccommodationsProvider implements ProviderInterface
{
    public function __construct(
        private Connection $connection,
        private RequestStack $requestStack,
        private CurrentUser $currentUser,
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

        $statusFilter = $query?->get('status') ?? 'all';
        $allowedStatuses = ['published', 'draft', 'all'];
        if (!\in_array($statusFilter, $allowedStatuses, true)) {
            $statusFilter = 'all';
        }

        // Team scoping is the security boundary: an owner only ever sees their own listings.
        $clauses = ['a.team_id = :teamId'];
        $params = ['teamId' => $this->currentUser->teamId()->toBinary()];
        $types = ['teamId' => ParameterType::BINARY];

        if ('all' !== $statusFilter) {
            $clauses[] = 'a.status = :status';
            $params['status'] = $statusFilter;
        }

        $whereSql = implode(' AND ', $clauses);

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
