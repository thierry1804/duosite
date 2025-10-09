<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration manuelle pour ajouter le tracking_token et générer les tokens
 */
final class Version20250101000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout manuel du tracking_token et génération des tokens pour les devis existants';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si la colonne existe déjà
        $this->addSql("
            SET @col_exists = 0;
            SELECT COUNT(*) INTO @col_exists 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME = 'quotes' 
            AND COLUMN_NAME = 'tracking_token' 
            AND TABLE_SCHEMA = DATABASE();
        ");
        
        // Ajouter la colonne seulement si elle n'existe pas
        $this->addSql("
            SET @sql = IF(@col_exists = 0, 
                'ALTER TABLE quotes ADD tracking_token VARCHAR(36) DEFAULT NULL', 
                'SELECT \"Column already exists\" as message'
            );
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
        
        // Générer les tokens pour les devis existants
        $this->addSql("
            UPDATE quotes 
            SET tracking_token = CONCAT(
                SUBSTRING(MD5(RAND()), 1, 8), '-',
                SUBSTRING(MD5(RAND()), 1, 4), '-',
                SUBSTRING(MD5(RAND()), 1, 4), '-',
                SUBSTRING(MD5(RAND()), 1, 4), '-',
                SUBSTRING(MD5(RAND()), 1, 12)
            )
            WHERE tracking_token IS NULL OR tracking_token = ''
        ");
        
        // Rendre la colonne NOT NULL
        $this->addSql("
            SET @sql = IF(@col_exists = 0, 
                'ALTER TABLE quotes MODIFY tracking_token VARCHAR(36) NOT NULL', 
                'SELECT \"Column already NOT NULL\" as message'
            );
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
        
        // Créer l'index unique
        $this->addSql("
            SET @index_exists = 0;
            SELECT COUNT(*) INTO @index_exists 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_NAME = 'quotes' 
            AND INDEX_NAME = 'UNIQ_8BDD0F1B5F7A6859' 
            AND TABLE_SCHEMA = DATABASE();
        ");
        
        $this->addSql("
            SET @sql = IF(@index_exists = 0, 
                'CREATE UNIQUE INDEX UNIQ_8BDD0F1B5F7A6859 ON quotes (tracking_token)', 
                'SELECT \"Index already exists\" as message'
            );
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
    }

    public function down(Schema $schema): void
    {
        // Supprimer l'index unique
        $this->addSql('DROP INDEX IF EXISTS UNIQ_8BDD0F1B5F7A6859 ON quotes');
        
        // Supprimer la colonne
        $this->addSql('ALTER TABLE quotes DROP COLUMN IF EXISTS tracking_token');
    }
}
