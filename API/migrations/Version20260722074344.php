<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260722074344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the donation table (Donation module)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE donation (id BINARY(16) NOT NULL, solidarity_project_id BINARY(16) NOT NULL, stripe_payment_intent_id VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, amount_cents INT NOT NULL, currency VARCHAR(3) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_31E581A0FC72F97E (stripe_payment_intent_id), INDEX IDX_DONATION_SOLIDARITY_PROJECT (solidarity_project_id), INDEX IDX_DONATION_STRIPE_INTENT (stripe_payment_intent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE donation');
    }
}
