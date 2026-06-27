<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add the cancelled_by_host flag to reservation. A host-initiated cancellation
 * fully refunds the guest (full compensation), so the initiator must be recorded.
 */
final class Version20260627100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cancelled_by_host column to reservation (default false).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD cancelled_by_host TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation DROP cancelled_by_host');
    }
}
