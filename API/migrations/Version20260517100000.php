<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create geography tables: region and locality.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE region (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', code VARCHAR(32) NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_region_code (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE locality (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, region_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_locality_region (region_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE locality');
        $this->addSql('DROP TABLE region');
    }
}
