<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240605164327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de l\'entité work_event_day';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE work_event_day (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, client_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, color VARCHAR(255) NOT NULL, INDEX IDX_96674416A76ED395 (user_id), UNIQUE INDEX UNIQ_9667441619EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE work_event_day ADD CONSTRAINT FK_96674416A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE work_event_day ADD CONSTRAINT FK_9667441619EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work_event_day DROP FOREIGN KEY FK_96674416A76ED395');
        $this->addSql('ALTER TABLE work_event_day DROP FOREIGN KEY FK_9667441619EB6921');
        $this->addSql('DROP TABLE work_event_day');
    }
}
