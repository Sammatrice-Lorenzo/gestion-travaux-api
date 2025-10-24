<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251011105912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creation table supplier and update product_invoice_file';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE supplier (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, vat_number VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_9B2A6C7EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE supplier ADD CONSTRAINT FK_9B2A6C7EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_invoice_file ADD supplier_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_invoice_file ADD CONSTRAINT FK_3FE761E2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3FE761E2ADD6D8C ON product_invoice_file (supplier_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE product_invoice_file DROP FOREIGN KEY FK_3FE761E2ADD6D8C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE supplier DROP FOREIGN KEY FK_9B2A6C7EA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE supplier
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_3FE761E2ADD6D8C ON product_invoice_file
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_invoice_file DROP supplier_id
        SQL);
    }
}
