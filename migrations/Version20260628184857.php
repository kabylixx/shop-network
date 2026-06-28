<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260628184857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the manager table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE manager (id BINARY(16) NOT NULL, name VARCHAR(150) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE manager');
    }
}
