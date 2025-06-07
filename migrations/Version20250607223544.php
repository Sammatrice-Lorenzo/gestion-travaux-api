<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250607223544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add entity work_image realted with work';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE work_image (id INT AUTO_INCREMENT NOT NULL, work_id INT NOT NULL, image_name VARCHAR(255) NOT NULL, updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_EDC67F70BB3453DB (work_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE work_image ADD CONSTRAINT FK_EDC67F70BB3453DB FOREIGN KEY (work_id) REFERENCES work (id)
        SQL);
    }

    public function down(Schema $schema): void
    {

        $dir = dirname(__DIR__) . '/public/work-images';
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ('.' !== $file && '..' !== $file) {
                    unlink("{$dir}/{$file}");
                }
            }
            rmdir($dir);
        }

        $this->addSql(<<<'SQL'
            ALTER TABLE work_image DROP FOREIGN KEY FK_EDC67F70BB3453DB
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE work_image
        SQL);
    }
}
