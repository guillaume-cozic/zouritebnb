<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the wishlist_item table. An item belongs either to an authenticated user
 * (user_id) or to an anonymous visitor tracked by a cookie correlation id
 * (correlation_id); exactly one of the two is set. Unique indexes guarantee an
 * accommodation is saved at most once per owner.
 */
final class Version20260626120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the wishlist_item table (per-user and per-correlation-id saved accommodations).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE wishlist_item (
                id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                user_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)',
                correlation_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)',
                accommodation_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX uniq_wishlist_user_accommodation (user_id, accommodation_id),
                UNIQUE INDEX uniq_wishlist_correlation_accommodation (correlation_id, accommodation_id),
                INDEX idx_wishlist_accommodation (accommodation_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE wishlist_item');
    }
}
