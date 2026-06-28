<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Dynamic pricing on accommodation: weekend surcharge, last-minute discount (+ its
 * day window) and seasonal/per-date price periods (stored as JSON, replaced wholesale).
 */
final class Version20260627110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dynamic pricing columns to accommodation (weekend surcharge, last-minute discount, price periods).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accommodation ADD weekend_surcharge_percentage DOUBLE PRECISION DEFAULT NULL, ADD last_minute_discount_percentage DOUBLE PRECISION DEFAULT NULL, ADD last_minute_days INT DEFAULT NULL, ADD price_periods JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accommodation DROP weekend_surcharge_percentage, DROP last_minute_discount_percentage, DROP last_minute_days, DROP price_periods');
    }
}
