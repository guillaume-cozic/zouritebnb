<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Baseline / genèse du schéma : squash des migrations initiales, qui partaient
 * d'ALTER TABLE (le schéma d'origine avait été créé via doctrine:schema:create,
 * pas par une migration CREATE) et ne pouvaient donc pas construire une base
 * neuve. Cette migration crée l'intégralité du schéma depuis les entités.
 */
final class Version20260720112641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Baseline schema (squash des migrations initiales)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE accommodation (
              id BINARY(16) NOT NULL,
              title VARCHAR(255) NOT NULL,
              description LONGTEXT NOT NULL,
              price DOUBLE PRECISION NOT NULL,
              status VARCHAR(20) NOT NULL,
              street VARCHAR(255) DEFAULT NULL,
              city VARCHAR(255) DEFAULT NULL,
              zip_code VARCHAR(20) DEFAULT NULL,
              country VARCHAR(100) DEFAULT NULL,
              latitude DOUBLE PRECISION DEFAULT NULL,
              longitude DOUBLE PRECISION DEFAULT NULL,
              bedrooms INT DEFAULT NULL,
              bathrooms INT DEFAULT NULL,
              max_guests INT DEFAULT NULL,
              single_beds INT DEFAULT NULL,
              double_beds INT DEFAULT NULL,
              amenities JSON DEFAULT NULL,
              check_in VARCHAR(5) DEFAULT NULL,
              check_out VARCHAR(5) DEFAULT NULL,
              team_id BINARY(16) DEFAULT NULL,
              weekly_promotion_percentage DOUBLE PRECISION DEFAULT NULL,
              region_id BINARY(16) DEFAULT NULL,
              cancellation_policy VARCHAR(20) DEFAULT 'flexible' NOT NULL,
              instant_booking TINYINT DEFAULT 0 NOT NULL,
              type VARCHAR(20) DEFAULT NULL,
              min_nights INT DEFAULT NULL,
              max_nights INT DEFAULT NULL,
              weekend_surcharge_percentage DOUBLE PRECISION DEFAULT NULL,
              last_minute_discount_percentage DOUBLE PRECISION DEFAULT NULL,
              last_minute_days INT DEFAULT NULL,
              price_periods JSON DEFAULT NULL,
              smoking_allowed TINYINT DEFAULT 0 NOT NULL,
              pets_allowed TINYINT DEFAULT 0 NOT NULL,
              parties_allowed TINYINT DEFAULT 0 NOT NULL,
              house_rules_notes LONGTEXT DEFAULT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE accommodation_gallery (
              accommodation_id BINARY(16) NOT NULL,
              photo_ids JSON NOT NULL,
              PRIMARY KEY (accommodation_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE accommodation_photo (
              id BINARY(16) NOT NULL,
              accommodation_id BINARY(16) NOT NULL,
              filename VARCHAR(255) NOT NULL,
              original_name VARCHAR(255) NOT NULL,
              mime_type VARCHAR(50) NOT NULL,
              size INT NOT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE conversation (
              id BINARY(16) NOT NULL,
              reservation_id BINARY(16) NOT NULL,
              accommodation_id BINARY(16) NOT NULL,
              team_id BINARY(16) NOT NULL,
              guest_user_id BINARY(16) NOT NULL,
              created_at DATETIME NOT NULL,
              UNIQUE INDEX UNIQ_8A8E26E9B83297E7 (reservation_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE conversation_message (
              id BINARY(16) NOT NULL,
              author_user_id BINARY(16) DEFAULT NULL,
              body LONGTEXT DEFAULT NULL,
              attachment_filename VARCHAR(255) DEFAULT NULL,
              sent_at DATETIME NOT NULL,
              is_system TINYINT NOT NULL,
              conversation_id BINARY(16) NOT NULL,
              INDEX IDX_2DEB3E759AC0396 (conversation_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE locality (
              id BINARY(16) NOT NULL,
              name VARCHAR(255) NOT NULL,
              region_id BINARY(16) NOT NULL,
              INDEX IDX_locality_region (region_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE outbox_email (
              id BINARY(16) NOT NULL,
              recipient_email VARCHAR(180) NOT NULL,
              recipient_name VARCHAR(255) DEFAULT NULL,
              subject VARCHAR(255) NOT NULL,
              html_body LONGTEXT NOT NULL,
              status VARCHAR(20) NOT NULL,
              attempts INT NOT NULL,
              created_at DATETIME NOT NULL,
              last_attempt_at DATETIME DEFAULT NULL,
              error LONGTEXT DEFAULT NULL,
              INDEX idx_outbox_email_status_created (status, created_at),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE outbox_sms (
              id BINARY(16) NOT NULL,
              recipient_phone VARCHAR(30) NOT NULL,
              text LONGTEXT NOT NULL,
              status VARCHAR(20) NOT NULL,
              attempts INT NOT NULL,
              created_at DATETIME NOT NULL,
              last_attempt_at DATETIME DEFAULT NULL,
              error LONGTEXT DEFAULT NULL,
              INDEX idx_outbox_sms_status_created (status, created_at),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE payment (
              id BINARY(16) NOT NULL,
              reservation_id BINARY(16) DEFAULT NULL,
              stripe_payment_intent_id VARCHAR(255) NOT NULL,
              status VARCHAR(20) NOT NULL,
              amount_cents INT NOT NULL,
              currency VARCHAR(3) NOT NULL,
              created_at DATETIME NOT NULL,
              refunded_amount_cents INT DEFAULT NULL,
              UNIQUE INDEX UNIQ_6D28840DFC72F97E (stripe_payment_intent_id),
              INDEX IDX_PAYMENT_RESERVATION (reservation_id),
              INDEX IDX_PAYMENT_STRIPE_INTENT (stripe_payment_intent_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE region (
              id BINARY(16) NOT NULL,
              code VARCHAR(32) NOT NULL,
              name VARCHAR(255) NOT NULL,
              UNIQUE INDEX UNIQ_region_code (code),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reservation (
              id BINARY(16) NOT NULL,
              accommodation_id BINARY(16) NOT NULL,
              team_id BINARY(16) NOT NULL,
              guest_user_id BINARY(16) DEFAULT NULL,
              check_in DATETIME NOT NULL,
              check_out DATETIME NOT NULL,
              guest_name VARCHAR(255) NOT NULL,
              guest_count INT DEFAULT 1 NOT NULL,
              status VARCHAR(20) NOT NULL,
              total_price DOUBLE PRECISION DEFAULT 0 NOT NULL,
              price_per_night DOUBLE PRECISION DEFAULT 0 NOT NULL,
              applied_discount_percentage DOUBLE PRECISION DEFAULT NULL,
              commission_amount DOUBLE PRECISION DEFAULT 0 NOT NULL,
              donation_amount DOUBLE PRECISION DEFAULT 0 NOT NULL,
              cancellation_policy VARCHAR(20) DEFAULT 'flexible' NOT NULL,
              cancelled_by_host TINYINT DEFAULT 0 NOT NULL,
              pending_modification JSON DEFAULT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE review (
              id BINARY(16) NOT NULL,
              type VARCHAR(20) NOT NULL,
              reservation_id BINARY(16) NOT NULL,
              author_user_id BINARY(16) NOT NULL,
              subject_accommodation_id BINARY(16) DEFAULT NULL,
              subject_user_id BINARY(16) DEFAULT NULL,
              rating INT NOT NULL,
              comment LONGTEXT NOT NULL,
              created_at DATETIME NOT NULL,
              host_reply LONGTEXT DEFAULT NULL,
              host_reply_at DATETIME DEFAULT NULL,
              UNIQUE INDEX uniq_review_author_reservation_type (
                author_user_id, reservation_id, type
              ),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE solidarity_project (
              id BINARY(16) NOT NULL,
              image_url VARCHAR(2048) DEFAULT NULL,
              status VARCHAR(20) NOT NULL,
              created_at DATETIME NOT NULL,
              is_default TINYINT DEFAULT 0 NOT NULL,
              translations JSON NOT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE team (
              id BINARY(16) NOT NULL,
              favorite_solidarity_project_id BINARY(16) DEFAULT NULL,
              iban VARCHAR(34) DEFAULT NULL,
              bic VARCHAR(11) DEFAULT NULL,
              bank_account_holder_name VARCHAR(70) DEFAULT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE team_invitation (
              id BINARY(16) NOT NULL,
              team_id BINARY(16) NOT NULL,
              email VARCHAR(255) NOT NULL,
              status VARCHAR(20) NOT NULL,
              created_at DATETIME NOT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE `user` (
              id BINARY(16) NOT NULL,
              email VARCHAR(180) NOT NULL,
              hashed_password VARCHAR(255) NOT NULL,
              team_id BINARY(16) NOT NULL,
              first_name VARCHAR(100) DEFAULT NULL,
              last_name VARCHAR(100) DEFAULT NULL,
              bio LONGTEXT DEFAULT NULL,
              avatar_filename VARCHAR(255) DEFAULT NULL,
              phone_number VARCHAR(30) DEFAULT NULL,
              roles JSON NOT NULL,
              verification_status VARCHAR(20) DEFAULT 'not_started' NOT NULL,
              identity_document_id BINARY(16) DEFAULT NULL,
              selfie_id BINARY(16) DEFAULT NULL,
              document_type VARCHAR(20) DEFAULT NULL,
              verified_at DATETIME DEFAULT NULL,
              email_verified TINYINT DEFAULT 0 NOT NULL,
              email_verified_at DATETIME DEFAULT NULL,
              UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_token (
              id BINARY(16) NOT NULL,
              user_id BINARY(16) NOT NULL,
              purpose VARCHAR(30) NOT NULL,
              hashed_token VARCHAR(64) NOT NULL,
              expires_at DATETIME NOT NULL,
              used_at DATETIME DEFAULT NULL,
              UNIQUE INDEX UNIQ_BDF55A63BD2BA26B (hashed_token),
              INDEX idx_user_token_user_purpose (user_id, purpose),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE wishlist_item (
              id BINARY(16) NOT NULL,
              user_id BINARY(16) DEFAULT NULL,
              correlation_id BINARY(16) DEFAULT NULL,
              accommodation_id BINARY(16) NOT NULL,
              created_at DATETIME NOT NULL,
              UNIQUE INDEX uniq_wishlist_user_accommodation (user_id, accommodation_id),
              UNIQUE INDEX uniq_wishlist_correlation_accommodation (
                correlation_id, accommodation_id
              ),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              conversation_message
            ADD
              CONSTRAINT FK_2DEB3E759AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conversation_message DROP FOREIGN KEY FK_2DEB3E759AC0396');
        $this->addSql('DROP TABLE accommodation');
        $this->addSql('DROP TABLE accommodation_gallery');
        $this->addSql('DROP TABLE accommodation_photo');
        $this->addSql('DROP TABLE conversation');
        $this->addSql('DROP TABLE conversation_message');
        $this->addSql('DROP TABLE locality');
        $this->addSql('DROP TABLE outbox_email');
        $this->addSql('DROP TABLE outbox_sms');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE region');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE solidarity_project');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE team_invitation');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE user_token');
        $this->addSql('DROP TABLE wishlist_item');
    }
}
