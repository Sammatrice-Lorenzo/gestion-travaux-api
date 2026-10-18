<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Qonto: qonto_supplier_id and qonto_id unique per user instead of globally';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_9B2A6C7EE7EE698E ON supplier');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SUPPLIER_USER_QONTO_SUPPLIER_ID ON supplier (user_id, qonto_supplier_id)');
        $this->addSql('DROP INDEX UNIQ_3FE761EBCCFA139 ON product_invoice_file');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PRODUCT_INVOICE_FILE_USER_QONTO_ID ON product_invoice_file (user_id, qonto_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_PRODUCT_INVOICE_FILE_USER_QONTO_ID ON product_invoice_file');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3FE761EBCCFA139 ON product_invoice_file (qonto_id)');
        $this->addSql('DROP INDEX UNIQ_SUPPLIER_USER_QONTO_SUPPLIER_ID ON supplier');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9B2A6C7EE7EE698E ON supplier (qonto_supplier_id)');
    }
}
