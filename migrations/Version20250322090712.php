<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250322090712 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Assurer que la colonne original_user_data est bien au format TEXT';
    }

    public function up(Schema $schema): void
    {
        // Utiliser SQL pour vérifier le type de la colonne et le changer si nécessaire
        $this->addSql('ALTER TABLE quotes MODIFY original_user_data TEXT NULL');
    }

    public function down(Schema $schema): void
    {
        // Rien à faire pour le down
    }
}
