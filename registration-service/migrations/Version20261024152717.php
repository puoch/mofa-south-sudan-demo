<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Optional, encrypted national ID number with a blind index.
 */
final class Version20261024152717 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional national ID number';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE registration_sensitive ADD national_id_number TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE registration_sensitive ADD national_id_index VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE INDEX registration_sensitive_national_id_index ON registration_sensitive (national_id_index)');
        $this->addSql("ALTER TABLE registration_sensitive ADD CONSTRAINT registration_sensitive_national_id_encrypted CHECK (national_id_number LIKE 'v1:%' AND (national_id_index IS NOT NULL) OR national_id_number IS NULL AND national_id_index IS NULL)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE registration_sensitive DROP CONSTRAINT registration_sensitive_national_id_encrypted');
        $this->addSql('DROP INDEX registration_sensitive_national_id_index');
        $this->addSql('ALTER TABLE registration_sensitive DROP national_id_number');
        $this->addSql('ALTER TABLE registration_sensitive DROP national_id_index');
    }
}
