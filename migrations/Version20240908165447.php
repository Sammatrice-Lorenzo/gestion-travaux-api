<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240908165447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de l\'entité product_invoice_file';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE product_invoice_file (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, path VARCHAR(255) NOT NULL, date DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE product_invoice_file');
    }
}
