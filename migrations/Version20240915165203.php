<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240915165203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la table product_invoice_file pour la gestion des factures des produits';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE product_invoice_file (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, path VARCHAR(255) NOT NULL, date DATETIME NOT NULL, INDEX IDX_3FE761EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_invoice_file ADD total_amount DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE product_invoice_file ADD CONSTRAINT FK_3FE761EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_invoice_file DROP FOREIGN KEY FK_3FE761EA76ED395');
        $this->addSql('ALTER TABLE product_invoice_file DROP total_amount');
        $this->addSql('DROP TABLE product_invoice_file');
    }
}
