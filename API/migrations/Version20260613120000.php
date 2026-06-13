<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the platform commission (margin) and the solidarity donation amount on each reservation, and backfill existing rows.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation
                ADD commission_amount DOUBLE PRECISION DEFAULT 0 NOT NULL,
                ADD donation_amount DOUBLE PRECISION DEFAULT 0 NOT NULL
        SQL);

        // Backfill historical reservations with the current rates (8% commission, 7% donation).
        $this->addSql(<<<'SQL'
            UPDATE reservation
            SET commission_amount = ROUND(total_price * 0.08, 2),
                donation_amount = ROUND(total_price * 0.07, 2)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation
                DROP commission_amount,
                DROP donation_amount
        SQL);
    }
}
