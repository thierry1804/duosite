<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250322175019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_proposals (id INT AUTO_INCREMENT NOT NULL, offer_id INT NOT NULL, quote_item_id INT NOT NULL, min_price NUMERIC(10, 2) DEFAULT NULL, max_price NUMERIC(10, 2) DEFAULT NULL, comments LONGTEXT DEFAULT NULL, images JSON NOT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', dimensions LONGTEXT DEFAULT NULL, weight NUMERIC(10, 2) DEFAULT NULL, INDEX IDX_CB1A8C0253C674EE (offer_id), INDEX IDX_CB1A8C02FD80FADA (quote_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE quote_offers (id INT AUTO_INCREMENT NOT NULL, quote_id INT NOT NULL, title VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(20) NOT NULL, total_price NUMERIC(10, 2) DEFAULT NULL, INDEX IDX_E0FEAA64DB805178 (quote_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shipping_options (id INT AUTO_INCREMENT NOT NULL, offer_id INT NOT NULL, name VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, price NUMERIC(10, 2) NOT NULL, estimated_delivery_days INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_367DFC4353C674EE (offer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_proposals ADD CONSTRAINT FK_CB1A8C0253C674EE FOREIGN KEY (offer_id) REFERENCES quote_offers (id)');
        $this->addSql('ALTER TABLE product_proposals ADD CONSTRAINT FK_CB1A8C02FD80FADA FOREIGN KEY (quote_item_id) REFERENCES quote_items (id)');
        $this->addSql('ALTER TABLE quote_offers ADD CONSTRAINT FK_E0FEAA64DB805178 FOREIGN KEY (quote_id) REFERENCES quotes (id)');
        $this->addSql('ALTER TABLE shipping_options ADD CONSTRAINT FK_367DFC4353C674EE FOREIGN KEY (offer_id) REFERENCES quote_offers (id)');
        $this->addSql('ALTER TABLE quotes DROP transaction_reference, DROP payment_date');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_proposals DROP FOREIGN KEY FK_CB1A8C0253C674EE');
        $this->addSql('ALTER TABLE product_proposals DROP FOREIGN KEY FK_CB1A8C02FD80FADA');
        $this->addSql('ALTER TABLE quote_offers DROP FOREIGN KEY FK_E0FEAA64DB805178');
        $this->addSql('ALTER TABLE shipping_options DROP FOREIGN KEY FK_367DFC4353C674EE');
        $this->addSql('DROP TABLE product_proposals');
        $this->addSql('DROP TABLE quote_offers');
        $this->addSql('DROP TABLE shipping_options');
        $this->addSql('ALTER TABLE quotes ADD transaction_reference VARCHAR(100) DEFAULT NULL, ADD payment_date DATETIME DEFAULT NULL');
    }
}
