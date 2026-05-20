<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420101618 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add updated_at to report';
    }

    public function up(Schema $schema): void
    {
        // Pridedam updated_at lauką report lentelei
        $this->addSql('ALTER TABLE report ADD updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Pašalinam updated_at jei rollback
        $this->addSql('ALTER TABLE report DROP updated_at');
    }
}