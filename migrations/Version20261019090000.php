<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ProductInvoiceFile: platform_snapshot (last values imported from the invoicing platform)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_invoice_file ADD platform_snapshot JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_invoice_file DROP platform_snapshot');
    }
}
