<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\DBAL\Connection;

/**
 * Returns the current owner's saved accommodations as card-ready summaries. Reads
 * the wishlist joined with the accommodation catalog via raw DBAL (kept inside the
 * provider, as for the other cross-table read models of the app).
 *
 * @implements ProviderInterface<WishlistItemOutput>
 */
final readonly class WishlistCollectionProvider implements ProviderInterface
{
    public function __construct(
        private WishlistOwnerResolver $ownerResolver,
        private Connection $connection,
    ) {
    }

    /**
     * @return WishlistItemOutput[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $owner = $this->ownerResolver->resolve();
        if (null === $owner) {
            return [];
        }

        $ownerColumn = $owner->isUser() ? 'user_id' : 'correlation_id';
        $ownerId = ($owner->userId ?? $owner->correlationId)->toRfc4122();

        $rows = $this->connection->executeQuery(
            <<<SQL
                SELECT
                    BIN_TO_UUID(a.id) AS accommodation_id,
                    a.title,
                    a.city,
                    a.country,
                    a.price,
                    (SELECT p.filename FROM accommodation_photo p WHERE p.accommodation_id = a.id ORDER BY p.id LIMIT 1) AS photo
                FROM wishlist_item w
                JOIN accommodation a ON a.id = w.accommodation_id
                WHERE w.{$ownerColumn} = UUID_TO_BIN(:ownerId)
                ORDER BY w.created_at DESC
                SQL,
            ['ownerId' => $ownerId],
        )->fetchAllAssociative();

        return array_map(static function (array $row): WishlistItemOutput {
            $output = new WishlistItemOutput();
            $output->accommodationId = $row['accommodation_id'];
            $output->title = $row['title'];
            $output->city = $row['city'];
            $output->country = $row['country'];
            $output->price = null !== $row['price'] ? (float) $row['price'] : null;
            $output->photoUrl = null !== $row['photo'] ? '/uploads/photos/'.$row['photo'] : null;

            return $output;
        }, $rows);
    }
}
