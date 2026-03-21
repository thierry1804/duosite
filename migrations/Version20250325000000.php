<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250325000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add import_order_items for multi-product orders';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE import_order_items (id INT AUTO_INCREMENT NOT NULL, import_order_id INT NOT NULL, product_code VARCHAR(20) NOT NULL, product_name VARCHAR(255) NOT NULL, product_price NUMERIC(12, 2) NOT NULL, color VARCHAR(100) NOT NULL, quantity INT DEFAULT 1 NOT NULL, INDEX IDX_IMPORT_ORDER_ITEMS_ORDER (import_order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE import_order_items ADD CONSTRAINT FK_IMPORT_ORDER_ITEMS_ORDER FOREIGN KEY (import_order_id) REFERENCES import_orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE import_orders CHANGE product_code product_code VARCHAR(20) DEFAULT NULL, CHANGE product_name product_name VARCHAR(255) DEFAULT NULL, CHANGE product_price product_price NUMERIC(12, 2) DEFAULT NULL, CHANGE color color VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE import_order_items DROP FOREIGN KEY FK_IMPORT_ORDER_ITEMS_ORDER');
        $this->addSql('DROP TABLE import_order_items');
        $this->addSql('ALTER TABLE import_orders CHANGE product_code product_code VARCHAR(20) NOT NULL, CHANGE product_name product_name VARCHAR(255) NOT NULL, CHANGE product_price product_price NUMERIC(12, 2) NOT NULL, CHANGE color color VARCHAR(100) NOT NULL');
    }
}
