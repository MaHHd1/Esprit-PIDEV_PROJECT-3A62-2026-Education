<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225103754 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evaluation ADD cours_id INT NOT NULL');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A5757ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
        $this->addSql('CREATE INDEX IDX_1323A5757ECF78B0 ON evaluation (cours_id)');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data BLOB NOT NULL');
        $this->addSql('ALTER TABLE soumission ADD updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A5757ECF78B0');
        $this->addSql('DROP INDEX IDX_1323A5757ECF78B0 ON evaluation');
        $this->addSql('ALTER TABLE evaluation ADD id_cours VARCHAR(100) NOT NULL, DROP cours_id');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data BLOB NOT NULL');
        $this->addSql('ALTER TABLE soumission DROP updated_at');
    }
}
