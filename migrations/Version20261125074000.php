<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261125074000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute quotes_enabled sur quote_settings pour activer/désactiver les demandes de devis';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quote_settings ADD quotes_enabled TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quote_settings DROP quotes_enabled');
    }
}
