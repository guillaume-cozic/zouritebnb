<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add the guest_count column to reservation. The number of travellers is captured
 * at booking time and validated against the accommodation capacity; existing rows
 * default to a single guest.
 */
final class Version20260626150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add guest_count column to reservation (default 1).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD guest_count INT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation DROP guest_count');
    }
}
