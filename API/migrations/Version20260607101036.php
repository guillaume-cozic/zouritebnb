<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607101036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create review table for accommodation and guest reviews tied to completed reservations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE review (
                id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                type VARCHAR(20) NOT NULL,
                reservation_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                author_user_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                subject_accommodation_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)',
                subject_user_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)',
                rating INT NOT NULL,
                comment LONGTEXT NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX uniq_review_author_reservation_type (author_user_id, reservation_id, type),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE review');
    }
}
