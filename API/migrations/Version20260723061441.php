<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Contact module: messages sent by visitors through the contact form.
 */
final class Version20260723061441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create contact_message table (Contact module)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE contact_message (id BINARY(16) NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, sent_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE contact_message');
    }
}
