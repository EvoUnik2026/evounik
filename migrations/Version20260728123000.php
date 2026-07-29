<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add topic position and homepage visibility fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE topic ADD position INT NOT NULL, ADD show_on_homepage TINYINT(1) NOT NULL default 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE topic DROP position, DROP show_on_homepage');
    }
}
