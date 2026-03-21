<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260321120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les champs d activation et invitation administrateur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD is_enabled TINYINT(1) NOT NULL DEFAULT 1, ADD admin_invitation_token VARCHAR(64) DEFAULT NULL, ADD admin_invitation_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD admin_otp_code_hash VARCHAR(255) DEFAULT NULL, ADD admin_otp_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD admin_activated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E2A9A89B9F ON users (admin_invitation_token)');
        $this->addSql("UPDATE users SET admin_activated_at = NOW() WHERE is_enabled = 1 AND roles LIKE '%ROLE_ADMIN%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_1483A5E2A9A89B9F ON users');
        $this->addSql('ALTER TABLE users DROP is_enabled, DROP admin_invitation_token, DROP admin_invitation_expires_at, DROP admin_otp_code_hash, DROP admin_otp_expires_at, DROP admin_activated_at');
    }
}
