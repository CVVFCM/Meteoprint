<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260625084500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add postcode to FFVoile spots';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE spot ADD postcode VARCHAR(5) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_B9327A73A95D0DD ON spot (postcode)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_B9327A73A95D0DD');
        $this->addSql('ALTER TABLE spot DROP postcode');
    }
}
