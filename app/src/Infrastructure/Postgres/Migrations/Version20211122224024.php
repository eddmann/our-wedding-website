<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211122224024 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE sent_invite_projection (
                id UUID NOT NULL PRIMARY KEY,
                code VARCHAR NOT NULL UNIQUE,
                type VARCHAR NOT NULL,
                invited_guests JSONB NOT NULL,
                submitted_at TIMESTAMPTZ,
                last_authenticated_at TIMESTAMPTZ
            );
        ');
    }
}
