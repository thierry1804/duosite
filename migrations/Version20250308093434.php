<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250308093434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE quotes (id INT AUTO_INCREMENT NOT NULL, quote_number VARCHAR(20) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(30) NOT NULL, company VARCHAR(150) DEFAULT NULL, product_type VARCHAR(50) NOT NULL, other_product_type VARCHAR(100) DEFAULT NULL, product_description LONGTEXT NOT NULL, quantity INT NOT NULL, budget DOUBLE PRECISION DEFAULT NULL, timeline VARCHAR(50) NOT NULL, services JSON NOT NULL COMMENT \'(DC2Type:json)\', additional_info LONGTEXT DEFAULT NULL, referral_source VARCHAR(50) DEFAULT NULL, privacy_policy TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', processed TINYINT(1) DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_A1B588C5AC28B117 (quote_number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE quotes');
    }
}
