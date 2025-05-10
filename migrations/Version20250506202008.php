<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250506202008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du cascade remove entre Work et Invoice';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE work DROP FOREIGN KEY FK_534E68802989F1FD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE work ADD CONSTRAINT FK_534E68802989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE work DROP FOREIGN KEY FK_534E68802989F1FD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE work ADD CONSTRAINT FK_534E68802989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
    }
}
