<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create password_reset_challenge table for client forgot-password OTP flow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE password_reset_challenge (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            public_token VARCHAR(64) NOT NULL,
            otp_code_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            attempts INT DEFAULT 0 NOT NULL,
            consumed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            last_sent_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_PWRST_USER (user_id),
            INDEX idx_password_reset_user_open (user_id, consumed_at),
            UNIQUE INDEX uniq_password_reset_public_token (public_token),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE password_reset_challenge ADD CONSTRAINT FK_PWRST_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE password_reset_challenge DROP FOREIGN KEY FK_PWRST_USER');
        $this->addSql('DROP TABLE password_reset_challenge');
    }
}
