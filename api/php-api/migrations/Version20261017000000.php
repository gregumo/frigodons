<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * In the production database, user_id_seq was still at 1 while the users go up to 57: creating a user would fail.
 */
final class Version20261017000000 extends AbstractMigration
{
    private const array SEQUENCES = [
        'user_id_seq' => '"user"',
        'cleaning_date_id_seq' => 'cleaning_date',
        'supervising_date_id_seq' => 'supervising_date',
        'mail_batch_id_seq' => 'mail_batch',
        'key_value_info_id_seq' => 'key_value_info',
    ];

    public function getDescription(): string
    {
        return 'Set the sequences after the highest existing ids';
    }

    public function up(Schema $schema): void
    {
        foreach (self::SEQUENCES as $sequence => $table) {
            $this->addSql(sprintf(
                'SELECT setval(\'%s\', COALESCE((SELECT MAX(id) FROM %s), 0) + 1, false)',
                $sequence,
                $table,
            ));
        }
    }

    public function down(Schema $schema): void
    {
    }
}
