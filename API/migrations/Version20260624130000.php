<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bio and avatar_filename columns to the user table for the public host profile.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE `user`
                ADD bio LONGTEXT DEFAULT NULL,
                ADD avatar_filename VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE `user`
                DROP bio,
                DROP avatar_filename
        SQL);
    }
}
