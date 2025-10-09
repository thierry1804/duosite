<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250323104123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la colonne rmb_mga_exchange_rate dans la table quote_offers';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quote_offers ADD rmb_mga_exchange_rate NUMERIC(10, 6) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quote_offers DROP rmb_mga_exchange_rate');
    }
}
