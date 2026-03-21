<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250324000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add import_products, import_orders, import_order_status_history tables for Commande import module';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE import_products (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) NOT NULL, name VARCHAR(255) NOT NULL, price NUMERIC(12, 2) NOT NULL, available_colors JSON NOT NULL, active TINYINT(1) DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_IMPORT_PRODUCTS_CODE (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE import_orders (id INT AUTO_INCREMENT NOT NULL, order_number VARCHAR(30) NOT NULL, tracking_token VARCHAR(36) NOT NULL, full_name VARCHAR(200) NOT NULL, phone VARCHAR(30) NOT NULL, email VARCHAR(180) NOT NULL, delivery_address LONGTEXT NOT NULL, product_code VARCHAR(20) NOT NULL, product_name VARCHAR(255) NOT NULL, product_price NUMERIC(12, 2) NOT NULL, color VARCHAR(100) NOT NULL, payment_method VARCHAR(30) NOT NULL, payment_reference VARCHAR(100) DEFAULT NULL, status VARCHAR(50) DEFAULT \'registered\' NOT NULL, cgv_accepted TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', weight VARCHAR(50) DEFAULT NULL, dimensions VARCHAR(100) DEFAULT NULL, shipping_estimate VARCHAR(255) DEFAULT NULL, shipping_info LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_IMPORT_ORDERS_ORDER (order_number), UNIQUE INDEX UNIQ_IMPORT_ORDERS_TOKEN (tracking_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE import_order_status_history (id INT AUTO_INCREMENT NOT NULL, import_order_id INT NOT NULL, old_status VARCHAR(64) NOT NULL, new_status VARCHAR(64) NOT NULL, changed_by VARCHAR(255) DEFAULT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_IMPORT_ORDER_HISTORY_ORDER (import_order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE import_order_status_history ADD CONSTRAINT FK_IMPORT_ORDER_HISTORY_ORDER FOREIGN KEY (import_order_id) REFERENCES import_orders (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE import_order_status_history DROP FOREIGN KEY FK_IMPORT_ORDER_HISTORY_ORDER');
        $this->addSql('DROP TABLE import_products');
        $this->addSql('DROP TABLE import_orders');
        $this->addSql('DROP TABLE import_order_status_history');
    }
}
