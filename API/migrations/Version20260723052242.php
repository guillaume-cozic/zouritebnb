<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260723052242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add extra_services JSON column on accommodation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accommodation ADD extra_services JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accommodation DROP extra_services');
    }
}
