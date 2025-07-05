<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250704202445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creation table token_notification_push for handle notification push with firebase';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE token_notification_push (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(255) NOT NULL, user_agent VARCHAR(255) NOT NULL, INDEX IDX_60E8D490A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE token_notification_push ADD CONSTRAINT FK_60E8D490A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE token_notification_push DROP FOREIGN KEY FK_60E8D490A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE token_notification_push
        SQL);
    }
}
