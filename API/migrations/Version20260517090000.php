<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_default flag on solidarity_project to designate the platform default project.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE solidarity_project ADD is_default TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE solidarity_project DROP is_default');
    }
}
