<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260514120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add guest_user_id to reservation (nullable, for B2C requested reservations).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD guest_user_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('CREATE INDEX IDX_RESERVATION_GUEST_USER ON reservation (guest_user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_RESERVATION_GUEST_USER ON reservation');
        $this->addSql('ALTER TABLE reservation DROP guest_user_id');
    }
}
