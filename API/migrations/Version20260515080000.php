<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260515080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix UUID columns on reservation.guest_user_id, conversation and conversation_message: CHAR(36) -> BINARY(16) to match Doctrine UuidType.';
    }

    public function up(Schema $schema): void
    {
        // reservation.guest_user_id
        $this->addSql('DROP INDEX IDX_RESERVATION_GUEST_USER ON reservation');
        $this->addSql("ALTER TABLE reservation MODIFY guest_user_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)'");
        $this->addSql('CREATE INDEX IDX_RESERVATION_GUEST_USER ON reservation (guest_user_id)');

        // conversation_message: drop FK first
        $this->addSql('ALTER TABLE conversation_message DROP FOREIGN KEY FK_MESSAGE_CONVERSATION');
        $this->addSql('DROP INDEX IDX_MESSAGE_CONVERSATION ON conversation_message');

        // conversation table
        $this->addSql('DROP INDEX UNIQ_CONVERSATION_RESERVATION ON conversation');
        $this->addSql('DROP INDEX IDX_CONVERSATION_TEAM ON conversation');
        $this->addSql('DROP INDEX IDX_CONVERSATION_GUEST ON conversation');
        $this->addSql("ALTER TABLE conversation
            MODIFY id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
            MODIFY reservation_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
            MODIFY accommodation_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
            MODIFY team_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
            MODIFY guest_user_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)'");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CONVERSATION_RESERVATION ON conversation (reservation_id)');
        $this->addSql('CREATE INDEX IDX_CONVERSATION_TEAM ON conversation (team_id)');
        $this->addSql('CREATE INDEX IDX_CONVERSATION_GUEST ON conversation (guest_user_id)');

        // conversation_message table
        $this->addSql("ALTER TABLE conversation_message
            MODIFY id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
            MODIFY conversation_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
            MODIFY author_user_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)'");
        $this->addSql('CREATE INDEX IDX_MESSAGE_CONVERSATION ON conversation_message (conversation_id)');
        $this->addSql('ALTER TABLE conversation_message ADD CONSTRAINT FK_MESSAGE_CONVERSATION FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE conversation_message DROP FOREIGN KEY FK_MESSAGE_CONVERSATION');
        $this->addSql('DROP INDEX IDX_MESSAGE_CONVERSATION ON conversation_message');
        $this->addSql("ALTER TABLE conversation_message
            MODIFY id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)',
            MODIFY conversation_id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)',
            MODIFY author_user_id CHAR(36) DEFAULT NULL COMMENT '(DC2Type:uuid)'");
        $this->addSql('CREATE INDEX IDX_MESSAGE_CONVERSATION ON conversation_message (conversation_id)');

        $this->addSql('DROP INDEX UNIQ_CONVERSATION_RESERVATION ON conversation');
        $this->addSql('DROP INDEX IDX_CONVERSATION_TEAM ON conversation');
        $this->addSql('DROP INDEX IDX_CONVERSATION_GUEST ON conversation');
        $this->addSql("ALTER TABLE conversation
            MODIFY id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)',
            MODIFY reservation_id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)',
            MODIFY accommodation_id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)',
            MODIFY team_id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)',
            MODIFY guest_user_id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)'");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CONVERSATION_RESERVATION ON conversation (reservation_id)');
        $this->addSql('CREATE INDEX IDX_CONVERSATION_TEAM ON conversation (team_id)');
        $this->addSql('CREATE INDEX IDX_CONVERSATION_GUEST ON conversation (guest_user_id)');
        $this->addSql('ALTER TABLE conversation_message ADD CONSTRAINT FK_MESSAGE_CONVERSATION FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE');

        $this->addSql('DROP INDEX IDX_RESERVATION_GUEST_USER ON reservation');
        $this->addSql("ALTER TABLE reservation MODIFY guest_user_id CHAR(36) DEFAULT NULL COMMENT '(DC2Type:uuid)'");
        $this->addSql('CREATE INDEX IDX_RESERVATION_GUEST_USER ON reservation (guest_user_id)');
    }
}
