<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a keyFigures JSON column to solidarity_project (headline numbers displayed on the project page).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE solidarity_project
                ADD key_figures JSON NOT NULL DEFAULT (JSON_ARRAY())
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE solidarity_project DROP key_figures
        SQL);
    }
}
