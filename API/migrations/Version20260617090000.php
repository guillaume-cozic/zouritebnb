<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a nullable phone_number column to the user (used for SMS notifications).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD phone_number VARCHAR(30) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP phone_number');
    }
}
