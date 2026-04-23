<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Aligne le tarif unitaire des articles de devis payants sur 2 000 Ar (cahier client / cohérence code).
 */
final class Version20260423120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fixe quote_settings.item_price à 2000 Ar';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE quote_settings SET item_price = 2000');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
