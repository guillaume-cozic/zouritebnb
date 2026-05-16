<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260514130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create conversation and conversation_message tables (one conversation per reservation).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE conversation (
                id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                reservation_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                accommodation_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                team_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                guest_user_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_CONVERSATION_RESERVATION (reservation_id),
                INDEX IDX_CONVERSATION_TEAM (team_id),
                INDEX IDX_CONVERSATION_GUEST (guest_user_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE conversation_message (
                id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                conversation_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                author_user_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)',
                body LONGTEXT NOT NULL,
                sent_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                is_system TINYINT(1) NOT NULL,
                INDEX IDX_MESSAGE_CONVERSATION (conversation_id),
                INDEX IDX_MESSAGE_SENT_AT (sent_at),
                PRIMARY KEY(id),
                CONSTRAINT FK_MESSAGE_CONVERSATION FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE conversation_message');
        $this->addSql('DROP TABLE conversation');
    }
}
