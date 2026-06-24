<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Make solidarity projects translatable: replace the single-language title,
 * description and key_figures columns with a single `translations` JSON column
 * keyed by locale ({"fr": {"title", "description", "keyFigures"}}). Existing rows
 * are migrated into the default locale ("fr").
 */
final class Version20260624120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move solidarity_project title/description/key_figures into a per-locale translations JSON column.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE solidarity_project
                ADD translations JSON NOT NULL DEFAULT (JSON_OBJECT())
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE solidarity_project
            SET translations = JSON_OBJECT(
                'fr', JSON_OBJECT(
                    'title', title,
                    'description', description,
                    'keyFigures', COALESCE(key_figures, JSON_ARRAY())
                )
            )
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE solidarity_project
                DROP title,
                DROP description,
                DROP key_figures
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE solidarity_project
                ADD title VARCHAR(255) NOT NULL DEFAULT '',
                ADD description LONGTEXT NOT NULL,
                ADD key_figures JSON NOT NULL DEFAULT (JSON_ARRAY())
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE solidarity_project
            SET title = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(translations, '$.fr.title')), ''),
                description = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(translations, '$.fr.description')), ''),
                key_figures = COALESCE(JSON_EXTRACT(translations, '$.fr.keyFigures'), JSON_ARRAY())
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE solidarity_project DROP translations
        SQL);
    }
}
