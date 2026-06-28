<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260628220809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the stock table with a unique (shop_id, product_id) constraint';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE stock (id BINARY(16) NOT NULL, product_id BINARY(16) NOT NULL, shop_id BINARY(16) NOT NULL, quantity INT UNSIGNED NOT NULL, UNIQUE INDEX uniq_stock_shop_product (shop_id, product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE stock');
    }
}
