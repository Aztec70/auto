<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260422092301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE service ADD work_day_from INT DEFAULT NULL, ADD work_day_to INT DEFAULT NULL, ADD work_time_from TIME DEFAULT NULL, ADD work_time_to TIME DEFAULT NULL, DROP working_hours');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE service ADD working_hours VARCHAR(100) DEFAULT NULL, DROP work_day_from, DROP work_day_to, DROP work_time_from, DROP work_time_to');
    }
}
