<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour générer les tracking tokens pour les devis existants
 */
final class Version20250101000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Génération des tracking tokens pour les devis existants';
    }

    public function up(Schema $schema): void
    {
        // Cette migration génère des UUIDs pour tous les devis existants qui n'ont pas encore de tracking_token
        // Note: En production, il faudrait utiliser une approche plus robuste avec des UUIDs réels
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
        
        // Maintenant rendre la colonne NOT NULL
        $this->addSql('ALTER TABLE quotes MODIFY tracking_token VARCHAR(36) NOT NULL');
        
        // Créer l'index unique sur tracking_token
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8BDD0F1B5F7A6859 ON quotes (tracking_token)');
    }

    public function down(Schema $schema): void
    {
        // Supprimer l'index unique
        $this->addSql('DROP INDEX UNIQ_8BDD0F1B5F7A6859 ON quotes');
        
        // Rendre la colonne nullable
        $this->addSql('ALTER TABLE quotes MODIFY tracking_token VARCHAR(36) DEFAULT NULL');
        
        // Supprimer tous les tracking tokens
        $this->addSql("UPDATE quotes SET tracking_token = NULL");
    }
}
