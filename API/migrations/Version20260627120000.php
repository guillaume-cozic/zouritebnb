<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add the host reply columns to review. The host of a reviewed accommodation can
 * publish a public reply to a guest's accommodation review.
 */
final class Version20260627120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add host_reply and host_reply_at columns to review.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE review ADD host_reply LONGTEXT DEFAULT NULL, ADD host_reply_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE review DROP host_reply, DROP host_reply_at');
    }
}
