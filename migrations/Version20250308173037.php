<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250308173037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quotes DROP product_type, DROP other_product_type, DROP product_description, DROP quantity, DROP budget');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quotes ADD product_type VARCHAR(50) NOT NULL, ADD other_product_type VARCHAR(100) DEFAULT NULL, ADD product_description LONGTEXT NOT NULL, ADD quantity INT NOT NULL, ADD budget DOUBLE PRECISION DEFAULT NULL');
    }
}
