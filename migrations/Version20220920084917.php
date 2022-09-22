<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220920084917 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE cleaning_date_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE supervising_range_date_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE cleaning_date (id INT NOT NULL, cleaner_id INT NOT NULL, day DATE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2A57DD2FEDDDAE19 ON cleaning_date (cleaner_id)');
        $this->addSql('CREATE TABLE supervising_range_date (id INT NOT NULL, supervisor_id INT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_831CC75019E9AC5F ON supervising_range_date (supervisor_id)');
        $this->addSql('ALTER TABLE cleaning_date ADD CONSTRAINT FK_2A57DD2FEDDDAE19 FOREIGN KEY (cleaner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE supervising_range_date ADD CONSTRAINT FK_831CC75019E9AC5F FOREIGN KEY (supervisor_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE cleaning_date_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE supervising_range_date_id_seq CASCADE');
        $this->addSql('ALTER TABLE cleaning_date DROP CONSTRAINT FK_2A57DD2FEDDDAE19');
        $this->addSql('ALTER TABLE supervising_range_date DROP CONSTRAINT FK_831CC75019E9AC5F');
        $this->addSql('DROP TABLE cleaning_date');
        $this->addSql('DROP TABLE supervising_range_date');
        $this->addSql('ALTER TABLE "user" DROP deleted_at');
    }
}
