<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter le système de tracking des devis
 */
final class Version20250101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du système de tracking des devis : tracking_token et table quote_status_history';
    }

    public function up(Schema $schema): void
    {
        // Ajouter la colonne tracking_token à la table quotes (nullable d'abord)
        $this->addSql('ALTER TABLE quotes ADD tracking_token VARCHAR(36) DEFAULT NULL');
        
        // Créer la table quote_status_history
        $this->addSql('CREATE TABLE quote_status_history (
            id INT AUTO_INCREMENT NOT NULL,
            quote_id INT NOT NULL,
            old_status VARCHAR(64) NOT NULL,
            new_status VARCHAR(64) NOT NULL,
            changed_by VARCHAR(255) DEFAULT NULL,
            comment LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_QUOTE_STATUS_HISTORY_QUOTE_ID (quote_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Ajouter la clé étrangère
        $this->addSql('ALTER TABLE quote_status_history ADD CONSTRAINT FK_QUOTE_STATUS_HISTORY_QUOTE_ID FOREIGN KEY (quote_id) REFERENCES quotes (id)');
    }

    public function down(Schema $schema): void
    {
        // Supprimer la table quote_status_history
        $this->addSql('DROP TABLE quote_status_history');
        
        // Supprimer la colonne tracking_token
        $this->addSql('ALTER TABLE quotes DROP tracking_token');
    }
}
