<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the outbox_sms table backing the transactional SMS outbox.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE outbox_sms (
                id BINARY(16) NOT NULL,
                recipient_phone VARCHAR(30) NOT NULL,
                text LONGTEXT NOT NULL,
                status VARCHAR(20) NOT NULL,
                attempts INT NOT NULL,
                created_at DATETIME NOT NULL,
                last_attempt_at DATETIME DEFAULT NULL,
                error LONGTEXT DEFAULT NULL,
                INDEX idx_outbox_sms_status_created (status, created_at),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE outbox_sms');
    }
}
