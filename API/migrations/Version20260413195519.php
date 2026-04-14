<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260413195519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    private const DEFAULT_TEAM_UUID = '00000000-0000-4000-8000-000000000001';

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE team (id BINARY(16) NOT NULL, favorite_solidarity_project_id BINARY(16) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE accommodation ADD team_id BINARY(16) DEFAULT NULL');

        // Seed default team and backfill existing accommodations
        $this->addSql('INSERT INTO team (id) VALUES (UUID_TO_BIN(:uuid))', ['uuid' => self::DEFAULT_TEAM_UUID]);
        $this->addSql('UPDATE accommodation SET team_id = UUID_TO_BIN(:uuid) WHERE team_id IS NULL', ['uuid' => self::DEFAULT_TEAM_UUID]);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE team');
        $this->addSql('ALTER TABLE accommodation DROP team_id');
    }
}
