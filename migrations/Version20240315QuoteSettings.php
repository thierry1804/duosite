<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240315QuoteSettings extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table quote_settings pour les paramètres de devis';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE quote_settings (
            id INT AUTO_INCREMENT NOT NULL,
            free_items_limit INT NOT NULL,
            item_price INT NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Insérer les valeurs par défaut
        $this->addSql('INSERT INTO quote_settings (free_items_limit, item_price) VALUES (5, 2000)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE quote_settings');
    }
} 