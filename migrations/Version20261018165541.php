<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018165541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supplier: address, city and country nullable, add qonto_supplier_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE supplier ADD qonto_supplier_id VARCHAR(255) DEFAULT NULL, CHANGE address address VARCHAR(255) DEFAULT NULL, CHANGE city city VARCHAR(255) DEFAULT NULL, CHANGE country country VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9B2A6C7EE7EE698E ON supplier (qonto_supplier_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_9B2A6C7EE7EE698E ON supplier');
        $this->addSql("UPDATE supplier SET address = COALESCE(address, ''), city = COALESCE(city, ''), country = COALESCE(country, '')");
        $this->addSql('ALTER TABLE supplier DROP qonto_supplier_id, CHANGE address address VARCHAR(255) NOT NULL, CHANGE city city VARCHAR(255) NOT NULL, CHANGE country country VARCHAR(255) NOT NULL');
    }
}
