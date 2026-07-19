<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260719120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow photo attachments on conversation messages (nullable body + attachment_filename).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE conversation_message CHANGE body body LONGTEXT DEFAULT NULL, ADD attachment_filename VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE conversation_message CHANGE body body LONGTEXT NOT NULL, DROP attachment_filename');
    }
}
