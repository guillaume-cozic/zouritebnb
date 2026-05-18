<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260518090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create payment table for Stripe payment intents lifecycle (Pending → Authorized → Captured/Cancelled/Failed).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE payment (
                id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                reservation_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)',
                stripe_payment_intent_id VARCHAR(255) NOT NULL,
                status VARCHAR(20) NOT NULL,
                amount_cents INT NOT NULL,
                currency CHAR(3) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_PAYMENT_STRIPE_INTENT (stripe_payment_intent_id),
                INDEX IDX_PAYMENT_RESERVATION (reservation_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE payment');
    }
}
