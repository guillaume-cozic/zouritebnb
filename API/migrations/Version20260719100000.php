<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * A cancelled reservation whose payment was already captured now triggers a real
 * Stripe refund (full or partial per the cancellation policy). The refunded amount
 * is persisted alongside the payment for accounting.
 */
final class Version20260719100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add refunded_amount_cents column to payment (real Stripe refund on cancellation).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment ADD refunded_amount_cents INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment DROP refunded_amount_cents');
    }
}
