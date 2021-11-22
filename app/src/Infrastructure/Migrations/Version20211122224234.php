<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211122224234 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE submitted_song_choice_projection (
                artist TEXT NOT NULL,
                track TEXT NOT NULL,
                submitted_at TIMESTAMPTZ NOT NULL
            );
        ');
    }
}
