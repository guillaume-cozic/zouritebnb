<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add the instant_booking column to accommodation. Hosts can enable instant
 * booking per accommodation so guest requests are auto-confirmed (and payment
 * captured) without manual approval. Existing rows default to disabled.
 */
final class Version20260626170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add instant_booking column to accommodation (default false).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accommodation ADD instant_booking TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accommodation DROP instant_booking');
    }
}
