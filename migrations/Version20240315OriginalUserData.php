<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240315OriginalUserData extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le champ original_user_data à la table quotes pour tracer les informations utilisateur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quotes ADD original_user_data JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quotes DROP original_user_data');
    }
} 