<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\SiteContactSettings;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée site_contact_settings + téléphones, horaires, réseaux et seed valeurs actuelles';
    }

    public function up(Schema $schema): void
    {
        $this->connection->executeStatement('CREATE TABLE site_contact_settings (id INT AUTO_INCREMENT NOT NULL, address_lines LONGTEXT NOT NULL, email VARCHAR(180) NOT NULL, whatsapp_default_message VARCHAR(255) DEFAULT NULL, map_embed_url LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->connection->executeStatement('CREATE TABLE contact_phone (id INT AUTO_INCREMENT NOT NULL, settings_id INT NOT NULL, label VARCHAR(100) DEFAULT NULL, number VARCHAR(50) NOT NULL, is_whatsapp TINYINT(1) NOT NULL, is_primary TINYINT(1) NOT NULL, position INT NOT NULL, INDEX IDX_696587D259949888 (settings_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->connection->executeStatement('CREATE TABLE opening_hour (id INT AUTO_INCREMENT NOT NULL, settings_id INT NOT NULL, day_of_week SMALLINT NOT NULL, is_closed TINYINT(1) NOT NULL, open_time TIME DEFAULT NULL, close_time TIME DEFAULT NULL, INDEX IDX_969BD76559949888 (settings_id), UNIQUE INDEX uniq_opening_hour_day (settings_id, day_of_week), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->connection->executeStatement('CREATE TABLE social_link (id INT AUTO_INCREMENT NOT NULL, settings_id INT NOT NULL, network VARCHAR(32) NOT NULL, url VARCHAR(500) NOT NULL, position INT NOT NULL, INDEX IDX_79BD4A9559949888 (settings_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->connection->executeStatement('ALTER TABLE contact_phone ADD CONSTRAINT FK_696587D259949888 FOREIGN KEY (settings_id) REFERENCES site_contact_settings (id) ON DELETE CASCADE');
        $this->connection->executeStatement('ALTER TABLE opening_hour ADD CONSTRAINT FK_969BD76559949888 FOREIGN KEY (settings_id) REFERENCES site_contact_settings (id) ON DELETE CASCADE');
        $this->connection->executeStatement('ALTER TABLE social_link ADD CONSTRAINT FK_79BD4A9559949888 FOREIGN KEY (settings_id) REFERENCES site_contact_settings (id) ON DELETE CASCADE');

        $this->connection->insert('site_contact_settings', [
            'address_lines' => "Antsakambahiny\nAmbohijanahary Antehiroka\nAntananarivo - Madagascar",
            'email' => 'contact@duoimport.mg',
            'whatsapp_default_message' => 'Bonjour Duo Import MDG, ',
            'map_embed_url' => SiteContactSettings::DEFAULT_MAP_EMBED_URL,
        ]);
        $settingsId = (int) $this->connection->lastInsertId();

        $phones = [
            ['Thierry', '+261 38 42 711 68', 1, 1, 0],
            [null, '+261 33 64 554 78', 0, 0, 1],
            [null, '+261 32 22 136 82', 0, 0, 2],
        ];
        foreach ($phones as [$label, $number, $wa, $primary, $position]) {
            $this->connection->insert('contact_phone', [
                'settings_id' => $settingsId,
                'label' => $label,
                'number' => $number,
                'is_whatsapp' => $wa,
                'is_primary' => $primary,
                'position' => $position,
            ]);
        }

        for ($day = 1; $day <= 7; ++$day) {
            $closed = $day > 5 ? 1 : 0;
            $this->connection->insert('opening_hour', [
                'settings_id' => $settingsId,
                'day_of_week' => $day,
                'is_closed' => $closed,
                'open_time' => $closed ? null : '08:00:00',
                'close_time' => $closed ? null : '17:00:00',
            ]);
        }

        $this->connection->insert('social_link', [
            'settings_id' => $settingsId,
            'network' => 'facebook',
            'url' => 'https://www.facebook.com/duoimportmdg',
            'position' => 0,
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contact_phone DROP FOREIGN KEY FK_696587D259949888');
        $this->addSql('ALTER TABLE opening_hour DROP FOREIGN KEY FK_969BD76559949888');
        $this->addSql('ALTER TABLE social_link DROP FOREIGN KEY FK_79BD4A9559949888');
        $this->addSql('DROP TABLE contact_phone');
        $this->addSql('DROP TABLE opening_hour');
        $this->addSql('DROP TABLE social_link');
        $this->addSql('DROP TABLE site_contact_settings');
    }
}
