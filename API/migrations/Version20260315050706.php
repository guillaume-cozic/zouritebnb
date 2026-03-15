<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260315050706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('DROP INDEX UNIQ_accommodation_gallery_id ON accommodation_gallery');
        $this->addSql('ALTER TABLE accommodation_gallery CHANGE accommodation_id accommodation_id BINARY(16) NOT NULL');
        $this->addSql('DROP INDEX UNIQ_accommodation_photo_id ON accommodation_photo');
        $this->addSql('DROP INDEX IDX_accommodation_photo_accommodation ON accommodation_photo');
        $this->addSql('ALTER TABLE accommodation_photo CHANGE id id BINARY(16) NOT NULL, CHANGE accommodation_id accommodation_id BINARY(16) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE accommodation_gallery CHANGE accommodation_id accommodation_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_accommodation_gallery_id ON accommodation_gallery (accommodation_id)');
        $this->addSql('ALTER TABLE accommodation_photo CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE accommodation_id accommodation_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_accommodation_photo_id ON accommodation_photo (id)');
        $this->addSql('CREATE INDEX IDX_accommodation_photo_accommodation ON accommodation_photo (accommodation_id)');
    }
}
