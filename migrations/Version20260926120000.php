<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Aligne quote_settings.free_items_limit sur les CGV (2 articles gratuits).
 */
final class Version20260926120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fixe quote_settings.free_items_limit à 2 (CGV art. 2.1)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE quote_settings SET free_items_limit = 2');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
