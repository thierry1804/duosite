<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260315150708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE import_order_status_history (id INT AUTO_INCREMENT NOT NULL, import_order_id INT NOT NULL, old_status VARCHAR(64) NOT NULL, new_status VARCHAR(64) NOT NULL, changed_by VARCHAR(255) DEFAULT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F409E1652430D3C2 (import_order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE import_order_status_history ADD CONSTRAINT FK_F409E1652430D3C2 FOREIGN KEY (import_order_id) REFERENCES import_orders (id)');
        $this->addSql('ALTER TABLE import_orders RENAME INDEX uniq_import_orders_order TO UNIQ_991E47F6551F0F81');
        $this->addSql('ALTER TABLE import_orders RENAME INDEX uniq_import_orders_token TO UNIQ_991E47F6B46BB896');
        $this->addSql('ALTER TABLE import_products RENAME INDEX uniq_import_products_code TO UNIQ_1A15E4A077153098');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE import_order_status_history DROP FOREIGN KEY FK_F409E1652430D3C2');
        $this->addSql('DROP TABLE import_order_status_history');
        $this->addSql('ALTER TABLE import_products RENAME INDEX uniq_1a15e4a077153098 TO UNIQ_IMPORT_PRODUCTS_CODE');
        $this->addSql('ALTER TABLE import_orders RENAME INDEX uniq_991e47f6b46bb896 TO UNIQ_IMPORT_ORDERS_TOKEN');
        $this->addSql('ALTER TABLE import_orders RENAME INDEX uniq_991e47f6551f0f81 TO UNIQ_IMPORT_ORDERS_ORDER');
    }
}
