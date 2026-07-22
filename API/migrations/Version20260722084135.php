<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260722084135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the activity_point table (ActivityPoint module)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE activity_point (id BINARY(16) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, category VARCHAR(32) NOT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL, article_url VARCHAR(500) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE activity_point');
    }
}
