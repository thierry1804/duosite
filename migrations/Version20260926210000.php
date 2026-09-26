<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260926210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée legal_page et seed CGV + politique de confidentialité';
    }

    public function up(Schema $schema): void
    {
        $this->connection->executeStatement('CREATE TABLE legal_page (
            id INT AUTO_INCREMENT NOT NULL,
            slug VARCHAR(64) NOT NULL,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX uniq_legal_page_slug (slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $cgv = $this->loadSeed('legal_page_cgv.html');
        $privacy = $this->loadSeed('legal_page_privacy.html');
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $this->connection->insert('legal_page', [
            'slug' => 'cgv',
            'title' => 'Conditions Générales de Vente',
            'content' => $cgv,
            'updated_at' => $now,
        ]);
        $this->connection->insert('legal_page', [
            'slug' => 'privacy_policy',
            'title' => 'Politique de confidentialité',
            'content' => $privacy,
            'updated_at' => $now,
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE legal_page');
    }

    private function loadSeed(string $filename): string
    {
        $path = dirname(__DIR__) . '/migrations/data/' . $filename;
        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('Seed file missing: %s', $path));
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException(sprintf('Unable to read seed file: %s', $path));
        }

        return $content;
    }
}
