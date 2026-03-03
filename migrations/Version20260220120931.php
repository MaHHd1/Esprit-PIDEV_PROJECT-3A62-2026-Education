<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260220120931 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cours_enseignant (cours_id INT NOT NULL, enseignant_id INT NOT NULL, INDEX IDX_845FDD887ECF78B0 (cours_id), INDEX IDX_845FDD88E455FCC0 (enseignant_id), PRIMARY KEY (cours_id, enseignant_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cours_enseignant ADD CONSTRAINT FK_845FDD887ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours_enseignant ADD CONSTRAINT FK_845FDD88E455FCC0 FOREIGN KEY (enseignant_id) REFERENCES Enseignant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours DROP FOREIGN KEY `FK_FDCA8C9CE455FCC0`');
        $this->addSql('DROP INDEX IDX_FDCA8C9CE455FCC0 ON cours');
        $this->addSql('ALTER TABLE cours DROP enseignant_id');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data BLOB NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cours_enseignant DROP FOREIGN KEY FK_845FDD887ECF78B0');
        $this->addSql('ALTER TABLE cours_enseignant DROP FOREIGN KEY FK_845FDD88E455FCC0');
        $this->addSql('DROP TABLE cours_enseignant');
        $this->addSql('ALTER TABLE cours ADD enseignant_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cours ADD CONSTRAINT `FK_FDCA8C9CE455FCC0` FOREIGN KEY (enseignant_id) REFERENCES enseignant (id)');
        $this->addSql('CREATE INDEX IDX_FDCA8C9CE455FCC0 ON cours (enseignant_id)');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data BLOB NOT NULL');
    }
}
