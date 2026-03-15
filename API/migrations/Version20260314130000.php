<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create accommodation_gallery table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE accommodation_gallery (accommodation_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', photo_ids JSON NOT NULL, UNIQUE INDEX UNIQ_accommodation_gallery_id (accommodation_id), PRIMARY KEY(accommodation_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE accommodation_gallery');
    }
}
