<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211122223716 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE available_food_choice_projection (
                id UUID NOT NULL PRIMARY KEY,
                name VARCHAR NOT NULL,
                guest_type VARCHAR NOT NULL,
                course VARCHAR NOT NULL
            );
        ');

        $this->addSql('
            CREATE INDEX food_choice_guest_type ON available_food_choice_projection (guest_type);
        ');
    }
}
