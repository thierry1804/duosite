<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250326000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add photo to import products and product photo to order items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE import_products ADD photo_filename VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE import_order_items ADD product_photo_filename VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE import_products DROP photo_filename');
        $this->addSql('ALTER TABLE import_order_items DROP product_photo_filename');
    }
}
