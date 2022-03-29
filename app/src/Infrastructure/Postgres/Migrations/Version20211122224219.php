<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211122224219 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE submitted_attending_guest_projection (
                id UUID NOT NULL PRIMARY KEY,
                invite_id UUID NOT NULL,
                invite_type VARCHAR NOT NULL,
                guest_type VARCHAR NOT NULL,
                name VARCHAR NOT NULL,
                chosen_food_choices JSONB
            );
        ');
    }
}
