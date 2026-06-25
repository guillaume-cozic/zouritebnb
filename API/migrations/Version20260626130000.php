<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add the cancellation_policy column to accommodation. Hosts pick a policy per
 * accommodation between "flexible" and "moderate"; existing rows default to the
 * most traveller-friendly policy ("flexible").
 */
final class Version20260626130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cancellation_policy column to accommodation (default flexible).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE accommodation ADD cancellation_policy VARCHAR(20) DEFAULT 'flexible' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accommodation DROP cancellation_policy');
    }
}
