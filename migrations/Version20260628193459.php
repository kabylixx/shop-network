<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260628193459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the shop table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE shop (id BINARY(16) NOT NULL, name VARCHAR(150) NOT NULL, address VARCHAR(255) NOT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL, manager_id BINARY(16) NOT NULL, status VARCHAR(16) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE shop');
    }
}
