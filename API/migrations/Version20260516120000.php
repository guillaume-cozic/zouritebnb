<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260516120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add iban, bic and bank_account_holder_name columns to team for payout details.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE team ADD iban VARCHAR(34) DEFAULT NULL, ADD bic VARCHAR(11) DEFAULT NULL, ADD bank_account_holder_name VARCHAR(70) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE team DROP iban, DROP bic, DROP bank_account_holder_name');
    }
}
