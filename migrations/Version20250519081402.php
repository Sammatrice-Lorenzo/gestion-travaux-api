<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250519081402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create column email in entity client';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE client ADD email VARCHAR(255) NOT NULL
        SQL);

        foreach ($this->connection->fetchAllAssociative('SELECT id FROM client') as $client) {
            $this->addSql('UPDATE client SET email = :email WHERE id = :idClient', [
                'email' => 'user@example.com',
                'idClient' => $client['id'],
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE client DROP email
        SQL);
    }
}
