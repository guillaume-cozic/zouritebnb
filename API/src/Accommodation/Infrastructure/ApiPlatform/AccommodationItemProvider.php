<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Shared\ApiPlatform\State\EntityProvider;
use Doctrine\DBAL\Connection;

/**
 * @implements ProviderInterface<AccommodationOutput>
 */
final readonly class AccommodationItemProvider implements ProviderInterface
{
    public function __construct(
        private EntityProvider $entityProvider,
        private Connection $connection,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?AccommodationOutput
    {
        /** @var AccommodationOutput|null $output */
        $output = $this->entityProvider->provide($operation, $uriVariables, $context);

        if (null === $output || null === $output->id) {
            return $output;
        }

        $sql = <<<'SQL'
            SELECT
                BIN_TO_UUID(p.id) AS id,
                p.filename
            FROM accommodation_photo p
            WHERE p.accommodation_id = UUID_TO_BIN(:accommodationId)
            ORDER BY p.id ASC
            SQL;

        $rows = $this->connection->executeQuery($sql, [
            'accommodationId' => $output->id,
        ])->fetchAllAssociative();

        $output->photos = array_map(static fn (array $row) => [
            'id' => $row['id'],
            'url' => '/uploads/photos/'.$row['filename'],
        ], $rows);

        return $output;
    }
}
