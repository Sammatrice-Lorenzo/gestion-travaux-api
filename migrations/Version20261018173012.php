<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018173012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Qonto: product_invoice_file qonto_id/source, qonto_sync_state table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_invoice_file ADD qonto_id VARCHAR(255) DEFAULT NULL, ADD source VARCHAR(20) DEFAULT \'manual\' NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3FE761EBCCFA139 ON product_invoice_file (qonto_id)');
        $this->addSql('CREATE TABLE qonto_sync_state (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, last_synced_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_A1A0C9A3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE qonto_sync_state ADD CONSTRAINT FK_A1A0C9A3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qonto_sync_state DROP FOREIGN KEY FK_A1A0C9A3A76ED395');
        $this->addSql('DROP TABLE qonto_sync_state');
        $this->addSql('DROP INDEX UNIQ_3FE761EBCCFA139 ON product_invoice_file');
        $this->addSql('ALTER TABLE product_invoice_file DROP qonto_id, DROP source');
    }
}
