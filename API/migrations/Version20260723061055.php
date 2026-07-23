<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Persist the frozen total of the extra services billed with the reservation
 * (once per stay, after the stay discount) as part of the reservation's
 * financial snapshot. The amount is already included in total_price; this
 * column keeps the breakdown for invoices and API output.
 */
final class Version20260723061055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reservation.extra_services_total (frozen total of extra services billed with the reservation)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD extra_services_total DOUBLE PRECISION DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation DROP extra_services_total');
    }
}
