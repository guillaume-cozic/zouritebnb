<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add the type and min/max nights columns to accommodation. Hosts can categorise
 * a listing (apartment, house, villa, studio, room, bungalow) and set a minimum
 * and/or maximum stay length. All columns are nullable (unspecified by default).
 */
final class Version20260626180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add type, min_nights and max_nights columns to accommodation.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accommodation ADD type VARCHAR(20) DEFAULT NULL, ADD min_nights INT DEFAULT NULL, ADD max_nights INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accommodation DROP type, DROP min_nights, DROP max_nights');
    }
}
