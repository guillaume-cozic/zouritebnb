<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds password-reset / email-verification support: a single-use, time-limited
 * user_token table (only the SHA-256 hash of each token is stored), plus email_verified
 * flags on the user. Existing users default to email_verified = 0 (unverified).
 */
final class Version20260626160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_token table and email_verified columns on user.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE user_token (
                id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                user_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                purpose VARCHAR(30) NOT NULL,
                hashed_token VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                used_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_USER_TOKEN_HASH (hashed_token),
                INDEX idx_user_token_user_purpose (user_id, purpose),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql("ALTER TABLE `user` ADD email_verified TINYINT(1) DEFAULT 0 NOT NULL, ADD email_verified_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_token');
        $this->addSql('ALTER TABLE `user` DROP email_verified, DROP email_verified_at');
    }
}
