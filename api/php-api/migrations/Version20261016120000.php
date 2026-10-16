<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * The composters padlock code is not shown to volunteers anymore.
 */
final class Version20261016120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove the unused composters padlock code';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM key_value_info WHERE key = \'composterPadlockCode\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('INSERT INTO key_value_info (id, key, value) VALUES (nextval(\'key_value_info_id_seq\'), \'composterPadlockCode\', \'0000\')');
    }
}
