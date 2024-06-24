<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240623204349 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de l\'entité invoice, invoice_line en liaison avec work';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, work_id INT NOT NULL, title VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_90651744BB3453DB (work_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoice_line (id INT AUTO_INCREMENT NOT NULL, invoice_id INT NOT NULL, localisation VARCHAR(255) DEFAULT NULL, description VARCHAR(255) NOT NULL, unit_price VARCHAR(255) NOT NULL, total_price_line NUMERIC(8, 2) NOT NULL, INDEX IDX_D3D1D6932989F1FD (invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744BB3453DB FOREIGN KEY (work_id) REFERENCES work (id)');
        $this->addSql('ALTER TABLE invoice_line ADD CONSTRAINT FK_D3D1D6932989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)');
        $this->addSql('ALTER TABLE work ADD invoice_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE work ADD CONSTRAINT FK_534E68802989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_534E68802989F1FD ON work (invoice_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work DROP FOREIGN KEY FK_534E68802989F1FD');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_90651744BB3453DB');
        $this->addSql('ALTER TABLE invoice_line DROP FOREIGN KEY FK_D3D1D6932989F1FD');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE invoice_line');
        $this->addSql('DROP INDEX UNIQ_534E68802989F1FD ON work');
        $this->addSql('ALTER TABLE work DROP invoice_id');
    }
}
