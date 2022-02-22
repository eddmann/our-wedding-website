<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220221231025 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE public.event_stream_pointers (
                pointer_name TEXT NOT NULL PRIMARY KEY,
                event_stream_pointer TEXT NOT NULL
            )
        ');
    }
}
