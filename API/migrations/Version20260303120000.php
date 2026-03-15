<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status column to accommodation table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE accommodation ADD status VARCHAR(20) NOT NULL DEFAULT 'draft'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accommodation DROP COLUMN status');
    }
}
