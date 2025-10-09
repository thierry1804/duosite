<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250308123456 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table users et met à jour la table quotes pour la relation user-quotes';
    }

    public function up(Schema $schema): void
    {
        // Création de la table users
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone VARCHAR(30) DEFAULT NULL, company VARCHAR(150) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_login_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Mise à jour de la table quotes pour ajouter la relation avec users et le statut
        $this->addSql('ALTER TABLE quotes ADD user_id INT DEFAULT NULL, ADD status VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE quotes ADD CONSTRAINT FK_A1B588C5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_A1B588C5A76ED395 ON quotes (user_id)');
    }

    public function down(Schema $schema): void
    {
        // Suppression de la relation entre quotes et users
        $this->addSql('ALTER TABLE quotes DROP FOREIGN KEY FK_A1B588C5A76ED395');
        $this->addSql('DROP INDEX IDX_A1B588C5A76ED395 ON quotes');
        $this->addSql('ALTER TABLE quotes DROP user_id, DROP status');
        
        // Suppression de la table users
        $this->addSql('DROP TABLE users');
    }
} 