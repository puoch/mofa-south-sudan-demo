<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Passport details become optional, as a complete set or not at all.
 */
final class Version20261024152207 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make passport details optional';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE registration_sensitive ALTER passport_number DROP NOT NULL');
        $this->addSql('ALTER TABLE registration_sensitive ALTER passport_issuing_country DROP NOT NULL');
        $this->addSql('ALTER TABLE registration_sensitive ALTER passport_expiry DROP NOT NULL');
        $this->addSql('ALTER TABLE registration_sensitive ALTER passport_index DROP NOT NULL');
        $this->addSql('ALTER TABLE registration_sensitive ADD CONSTRAINT registration_sensitive_passport_complete CHECK ((passport_number IS NULL) = (passport_issuing_country IS NULL) AND (passport_number IS NULL) = (passport_expiry IS NULL) AND (passport_number IS NULL) = (passport_index IS NULL))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE registration_sensitive DROP CONSTRAINT registration_sensitive_passport_complete');
        $this->addSql('ALTER TABLE registration_sensitive ALTER passport_number SET NOT NULL');
        $this->addSql('ALTER TABLE registration_sensitive ALTER passport_issuing_country SET NOT NULL');
        $this->addSql('ALTER TABLE registration_sensitive ALTER passport_expiry SET NOT NULL');
        $this->addSql('ALTER TABLE registration_sensitive ALTER passport_index SET NOT NULL');
    }
}
