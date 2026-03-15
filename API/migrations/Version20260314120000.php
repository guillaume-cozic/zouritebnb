<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create accommodation_photo table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE accommodation_photo (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', accommodation_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(50) NOT NULL, size INT NOT NULL, UNIQUE INDEX UNIQ_accommodation_photo_id (id), INDEX IDX_accommodation_photo_accommodation (accommodation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE accommodation_photo');
    }
}
