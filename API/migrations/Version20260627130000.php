<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Store a guest-requested date change awaiting the host's approval, as JSON on the
 * reservation (proposed range + recomputed frozen price). Applied on approval,
 * discarded on rejection.
 */
final class Version20260627130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pending_modification column to reservation (guest date-change awaiting host approval).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD pending_modification JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation DROP pending_modification');
    }
}
