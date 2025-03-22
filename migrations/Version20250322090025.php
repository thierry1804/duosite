<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250322090025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la colonne payment_status à la table quotes';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quotes ADD payment_status VARCHAR(20) NOT NULL DEFAULT \'not_required\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quotes DROP payment_status');
    }
}
