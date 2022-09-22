<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220920205850 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE supervising_range_date_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE supervising_date_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE supervising_date (id INT NOT NULL, supervisor_id INT NOT NULL, day DATE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DC02A65619E9AC5F ON supervising_date (supervisor_id)');
        $this->addSql('ALTER TABLE supervising_date ADD CONSTRAINT FK_DC02A65619E9AC5F FOREIGN KEY (supervisor_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE supervising_range_date DROP CONSTRAINT fk_831cc75019e9ac5f');
        $this->addSql('DROP TABLE supervising_range_date');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE supervising_date_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE supervising_range_date_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE supervising_range_date (id INT NOT NULL, supervisor_id INT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_831cc75019e9ac5f ON supervising_range_date (supervisor_id)');
        $this->addSql('ALTER TABLE supervising_range_date ADD CONSTRAINT fk_831cc75019e9ac5f FOREIGN KEY (supervisor_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE supervising_date DROP CONSTRAINT FK_DC02A65619E9AC5F');
        $this->addSql('DROP TABLE supervising_date');
    }
}
