<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour créer la table quote_status_history
 */
final class Version20250101000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table quote_status_history';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si la table existe déjà
        $this->addSql("
            SET @table_exists = 0;
            SELECT COUNT(*) INTO @table_exists 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_NAME = 'quote_status_history' 
            AND TABLE_SCHEMA = DATABASE();
        ");
        
        // Créer la table seulement si elle n'existe pas
        $this->addSql("
            SET @sql = IF(@table_exists = 0, 
                'CREATE TABLE quote_status_history (
                    id INT AUTO_INCREMENT NOT NULL,
                    quote_id INT NOT NULL,
                    old_status VARCHAR(64) NOT NULL,
                    new_status VARCHAR(64) NOT NULL,
                    changed_by VARCHAR(255) DEFAULT NULL,
                    comment LONGTEXT DEFAULT NULL,
                    created_at DATETIME NOT NULL,
                    INDEX IDX_QUOTE_STATUS_HISTORY_QUOTE_ID (quote_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB', 
                'SELECT \"Table already exists\" as message'
            );
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
        
        // Ajouter la clé étrangère
        $this->addSql("
            SET @fk_exists = 0;
            SELECT COUNT(*) INTO @fk_exists 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'quote_status_history' 
            AND CONSTRAINT_NAME = 'FK_QUOTE_STATUS_HISTORY_QUOTE_ID' 
            AND TABLE_SCHEMA = DATABASE();
        ");
        
        $this->addSql("
            SET @sql = IF(@fk_exists = 0 AND @table_exists = 0, 
                'ALTER TABLE quote_status_history ADD CONSTRAINT FK_QUOTE_STATUS_HISTORY_QUOTE_ID FOREIGN KEY (quote_id) REFERENCES quotes (id)', 
                'SELECT \"Foreign key already exists or table does not exist\" as message'
            );
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
    }

    public function down(Schema $schema): void
    {
        // Supprimer la table quote_status_history
        $this->addSql('DROP TABLE IF EXISTS quote_status_history');
    }
}
