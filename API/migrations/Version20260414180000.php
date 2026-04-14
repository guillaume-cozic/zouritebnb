<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260414180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add total_price, price_per_night and applied_discount_percentage to reservation.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD total_price DOUBLE PRECISION DEFAULT 0 NOT NULL, ADD price_per_night DOUBLE PRECISION DEFAULT 0 NOT NULL, ADD applied_discount_percentage DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation DROP total_price, DROP price_per_night, DROP applied_discount_percentage');
    }
}
