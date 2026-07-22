<?php

declare(strict_types=1);

namespace App\ActivityPoint\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * Paginated list of every activity point for the admin back-office, ordered by
 * name, with optional "search" (name/description) and "category" filters.
 *
 * @implements ProviderInterface<AdminActivityPointOutput>
 */
final readonly class AdminActivityPointCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Connection $connection,
        private Pagination $pagination,
    ) {
    }

    /**
     * @return TraversablePaginator<AdminActivityPointOutput>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        [$page, $offset, $limit] = $this->pagination->getPagination($operation, $context);

        $filters = $context['filters'] ?? [];
        $conditions = [];
        $params = [];

        $search = trim((string) ($filters['search'] ?? ''));
        if ('' !== $search) {
            $conditions[] = '(p.name LIKE :search OR p.description LIKE :search)';
            $params['search'] = '%'.$search.'%';
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ('' !== $category) {
            $conditions[] = 'p.category = :category';
            $params['category'] = $category;
        }

        $where = $conditions ? ' WHERE '.implode(' AND ', $conditions) : '';

        $totalItems = (int) $this->connection->executeQuery(
            'SELECT COUNT(*) FROM activity_point p'.$where,
            $params,
        )->fetchOne();

        $sql = <<<SQL
            SELECT
                BIN_TO_UUID(p.id) AS id,
                p.name,
                p.description,
                p.category,
                p.latitude,
                p.longitude,
                p.article_url
            FROM activity_point p
            {$where}
            ORDER BY p.name ASC
            LIMIT :limit OFFSET :offset
            SQL;

        $rows = $this->connection->executeQuery(
            $sql,
            [...$params, 'limit' => $limit, 'offset' => $offset],
            ['limit' => ParameterType::INTEGER, 'offset' => ParameterType::INTEGER],
        )->fetchAllAssociative();

        $points = [];
        foreach ($rows as $row) {
            $output = new AdminActivityPointOutput();
            $output->id = $row['id'];
            $output->name = $row['name'];
            $output->description = $row['description'];
            $output->category = $row['category'];
            $output->latitude = (float) $row['latitude'];
            $output->longitude = (float) $row['longitude'];
            $output->articleUrl = $row['article_url'];
            $points[] = $output;
        }

        return new TraversablePaginator(new \ArrayIterator($points), $page, $limit, $totalItems);
    }
}
