<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * House rules per accommodation: smoking/pets/parties toggles set by the host plus
 * free-text additional rules, displayed on the accommodation page.
 */
final class Version20260719110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add house rules columns to accommodation (smoking/pets/parties + free-text notes).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accommodation ADD smoking_allowed TINYINT(1) DEFAULT 0 NOT NULL, ADD pets_allowed TINYINT(1) DEFAULT 0 NOT NULL, ADD parties_allowed TINYINT(1) DEFAULT 0 NOT NULL, ADD house_rules_notes TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accommodation DROP smoking_allowed, DROP pets_allowed, DROP parties_allowed, DROP house_rules_notes');
    }
}
