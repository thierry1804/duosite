<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250322160126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs transaction_reference et payment_date à la table quotes';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quotes ADD transaction_reference VARCHAR(100) DEFAULT NULL, ADD payment_date DATETIME DEFAULT NULL, CHANGE original_user_data original_user_data LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quotes DROP transaction_reference, DROP payment_date, CHANGE original_user_data original_user_data TEXT DEFAULT NULL');
    }
}
