<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250308153528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le champ status à la table quotes si nécessaire';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si la colonne status existe déjà
        $columns = $this->connection->fetchAllAssociative('SHOW COLUMNS FROM quotes');
        $columnNames = array_column($columns, 'Field');
        
        // Ajouter la colonne status si elle n'existe pas
        if (!in_array('status', $columnNames)) {
            $this->addSql('ALTER TABLE quotes ADD status VARCHAR(50) NOT NULL');
            // Définir le statut par défaut pour les devis existants
            $this->addSql('UPDATE quotes SET status = "pending"');
        }
        
        // Vérifier si la contrainte de clé étrangère existe déjà
        $foreignKeys = $this->connection->fetchAllAssociative('SHOW CREATE TABLE quotes');
        $createTableStatement = $foreignKeys[0]['Create Table'] ?? '';
        
        // Ajouter la contrainte de clé étrangère si elle n'existe pas
        if (strpos($createTableStatement, 'FK_A1B588C5A76ED395') === false && in_array('user_id', $columnNames)) {
            $this->addSql('ALTER TABLE quotes ADD CONSTRAINT FK_A1B588C5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
            $this->addSql('CREATE INDEX IDX_A1B588C5A76ED395 ON quotes (user_id)');
        }
    }

    public function down(Schema $schema): void
    {
        // Vérifier si la colonne status existe
        $columns = $this->connection->fetchAllAssociative('SHOW COLUMNS FROM quotes');
        $columnNames = array_column($columns, 'Field');
        
        // Supprimer la contrainte de clé étrangère si elle existe
        $foreignKeys = $this->connection->fetchAllAssociative('SHOW CREATE TABLE quotes');
        $createTableStatement = $foreignKeys[0]['Create Table'] ?? '';
        
        if (strpos($createTableStatement, 'FK_A1B588C5A76ED395') !== false) {
            $this->addSql('ALTER TABLE quotes DROP FOREIGN KEY FK_A1B588C5A76ED395');
            $this->addSql('DROP INDEX IDX_A1B588C5A76ED395 ON quotes');
        }
        
        // Supprimer la colonne status si elle existe
        if (in_array('status', $columnNames)) {
            $this->addSql('ALTER TABLE quotes DROP status');
        }
    }
}
