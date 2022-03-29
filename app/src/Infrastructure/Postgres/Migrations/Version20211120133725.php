<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211120133725 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE event_store (
                id SERIAL PRIMARY KEY,
                aggregate_name VARCHAR NOT NULL,
                aggregate_id UUID NOT NULL,
                aggregate_version INT NOT NULL,
                event_name VARCHAR NOT NULL,
                event_data JSONB NOT NULL,
                UNIQUE (aggregate_name, aggregate_id, aggregate_version)
            );
        ');

        $this->addSql('
            CREATE INDEX event_store_aggregate ON event_store (aggregate_name, aggregate_id);
        ');
    }
}
