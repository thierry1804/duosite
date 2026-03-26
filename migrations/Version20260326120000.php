<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le type d\'envoi (maritime / aérien) sur les commandes d\'import';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE import_orders ADD shipping_type VARCHAR(32) NOT NULL DEFAULT 'maritime'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE import_orders DROP shipping_type');
    }
}
