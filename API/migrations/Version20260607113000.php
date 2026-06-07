<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add identity verification columns to the user table (verification_status, identity_document_id, selfie_id, document_type, verified_at).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE `user`
                ADD verification_status VARCHAR(20) NOT NULL DEFAULT 'not_started',
                ADD identity_document_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)',
                ADD selfie_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)',
                ADD document_type VARCHAR(20) DEFAULT NULL,
                ADD verified_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE `user`
                DROP verification_status,
                DROP identity_document_id,
                DROP selfie_id,
                DROP document_type,
                DROP verified_at
        SQL);
    }
}
