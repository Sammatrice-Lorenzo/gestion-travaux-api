<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260621101840 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove legacy invoice.work_id column after Doctrine ORM 3 Work/Invoice mapping fix';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('invoice') || !$schema->getTable('invoice')->hasColumn('work_id')) {
            return;
        }

        $this->addSql(<<<'SQL'
            ALTER TABLE invoice DROP FOREIGN KEY IF EXISTS FK_90651744BB3453DB
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IF EXISTS UNIQ_90651744BB3453DB ON invoice
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE invoice DROP COLUMN work_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('invoice') || $schema->getTable('invoice')->hasColumn('work_id')) {
            return;
        }

        $this->addSql(<<<'SQL'
            ALTER TABLE invoice ADD work_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_90651744BB3453DB ON invoice (work_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE invoice ADD CONSTRAINT FK_90651744BB3453DB FOREIGN KEY (work_id) REFERENCES work (id)
        SQL);
    }
}
