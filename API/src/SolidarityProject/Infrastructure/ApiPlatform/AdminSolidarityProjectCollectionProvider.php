<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\SolidarityProject\Domain\Entity\SolidarityProject;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * Lists every solidarity project of the platform for the admin back-office, the
 * platform "coup de cœur" (default) first, then most recently created first.
 *
 * @implements ProviderInterface<AdminSolidarityProjectOutput>
 */
final readonly class AdminSolidarityProjectCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Connection $connection,
        private Pagination $pagination,
    ) {
    }

    /**
     * @return TraversablePaginator<AdminSolidarityProjectOutput>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        [$page, $offset, $limit] = $this->pagination->getPagination($operation, $context);

        $filters = $context['filters'] ?? [];
        $conditions = [];
        $params = [];
        $types = [];

        $search = trim((string) ($filters['search'] ?? ''));
        if ('' !== $search) {
            // Search the title/description of every locale stored in the translations JSON.
            $conditions[] = <<<'SQL'
                (
                    JSON_UNQUOTE(JSON_EXTRACT(p.translations, '$.fr.title')) LIKE :search
                    OR JSON_UNQUOTE(JSON_EXTRACT(p.translations, '$.fr.description')) LIKE :search
                    OR JSON_UNQUOTE(JSON_EXTRACT(p.translations, '$.en.title')) LIKE :search
                    OR JSON_UNQUOTE(JSON_EXTRACT(p.translations, '$.en.description')) LIKE :search
                )
                SQL;
            $params['search'] = '%'.$search.'%';
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ('' !== $status) {
            $conditions[] = 'p.status = :status';
            $params['status'] = $status;
        }

        $where = $conditions ? ' WHERE '.implode(' AND ', $conditions) : '';

        $totalItems = (int) $this->connection->executeQuery(
            'SELECT COUNT(*) FROM solidarity_project p'.$where,
            $params,
            $types,
        )->fetchOne();

        $sql = <<<SQL
            SELECT
                BIN_TO_UUID(p.id) AS id,
                p.translations,
                p.image_url,
                p.status,
                p.created_at,
                p.is_default
            FROM solidarity_project p
            {$where}
            ORDER BY p.is_default DESC, p.created_at DESC
            LIMIT :limit OFFSET :offset
            SQL;

        $rows = $this->connection->executeQuery(
            $sql,
            [...$params, 'limit' => $limit, 'offset' => $offset],
            [...$types, 'limit' => ParameterType::INTEGER, 'offset' => ParameterType::INTEGER],
        )->fetchAllAssociative();

        $projects = [];
        foreach ($rows as $row) {
            $translations = json_decode((string) $row['translations'], true);
            $translations = \is_array($translations) ? $translations : [];
            $default = $translations[SolidarityProject::DEFAULT_LOCALE] ?? ['title' => null, 'description' => null, 'keyFigures' => []];

            $output = new AdminSolidarityProjectOutput();
            $output->id = $row['id'];
            $output->title = $default['title'] ?? null;
            $output->description = $default['description'] ?? null;
            $output->imageUrl = $row['image_url'];
            $output->status = $row['status'];
            $output->createdAt = (new \DateTimeImmutable((string) $row['created_at']))->format(\DateTimeInterface::ATOM);
            $output->isDefault = (bool) $row['is_default'];
            $output->keyFigures = $default['keyFigures'] ?? [];
            $output->translations = $translations;
            $projects[] = $output;
        }

        return new TraversablePaginator(new \ArrayIterator($projects), $page, $limit, $totalItems);
    }
}
