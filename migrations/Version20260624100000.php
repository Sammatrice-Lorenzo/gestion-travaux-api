<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create supplier_return_invoice_file table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE supplier_return_invoice_file (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, supplier_id INT DEFAULT NULL, linked_product_invoice_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, path VARCHAR(512) NOT NULL, date DATETIME NOT NULL, credit_amount DOUBLE PRECISION NOT NULL, INDEX IDX_SUPPLIER_RETURN_USER (user_id), INDEX IDX_SUPPLIER_RETURN_SUPPLIER (supplier_id), INDEX IDX_SUPPLIER_RETURN_LINKED (linked_product_invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE supplier_return_invoice_file ADD CONSTRAINT FK_SUPPLIER_RETURN_USER FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE supplier_return_invoice_file ADD CONSTRAINT FK_SUPPLIER_RETURN_SUPPLIER FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
        $this->addSql('ALTER TABLE supplier_return_invoice_file ADD CONSTRAINT FK_SUPPLIER_RETURN_LINKED FOREIGN KEY (linked_product_invoice_id) REFERENCES product_invoice_file (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE supplier_return_invoice_file DROP FOREIGN KEY FK_SUPPLIER_RETURN_USER');
        $this->addSql('ALTER TABLE supplier_return_invoice_file DROP FOREIGN KEY FK_SUPPLIER_RETURN_SUPPLIER');
        $this->addSql('ALTER TABLE supplier_return_invoice_file DROP FOREIGN KEY FK_SUPPLIER_RETURN_LINKED');
        $this->addSql('DROP TABLE supplier_return_invoice_file');
    }
}
