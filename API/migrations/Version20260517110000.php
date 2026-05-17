<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add region_id to accommodation (nullable, indexed) to associate each accommodation with a Geography region.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE accommodation ADD region_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)'");
        $this->addSql('CREATE INDEX IDX_ACCOMMODATION_REGION ON accommodation (region_id)');
        $this->addSql("UPDATE accommodation SET region_id = UUID_TO_BIN('00000000-0000-4000-8000-00000000000a') WHERE region_id IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_ACCOMMODATION_REGION ON accommodation');
        $this->addSql('ALTER TABLE accommodation DROP region_id');
    }
}
