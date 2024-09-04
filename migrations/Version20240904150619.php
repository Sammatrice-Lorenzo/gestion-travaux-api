<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240904150619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de colonne total_amount dans work';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work ADD total_amount DOUBLE PRECISION NOT NULL');
        
        $works = $this->connection->fetchAllAssociative("SELECT id FROM work");
        foreach ($works as $work) {
            $this->addSql("UPDATE work SET total_amount = 0.0 WHERE id = :id_work", ['id_work' => $work['id']]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work DROP total_amount');
    }
}
