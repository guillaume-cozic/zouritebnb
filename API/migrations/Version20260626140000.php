<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Snapshot the cancellation policy onto each reservation at booking time, so a later
 * change of the accommodation's policy never rewrites the terms of existing
 * reservations. Existing rows default to the most traveller-friendly policy ("flexible").
 */
final class Version20260626140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cancellation_policy snapshot column to reservation (default flexible).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE reservation ADD cancellation_policy VARCHAR(20) DEFAULT 'flexible' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation DROP cancellation_policy');
    }
}
