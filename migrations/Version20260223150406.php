<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223150406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE resultat_quiz (id INT AUTO_INCREMENT NOT NULL, id_etudiant INT NOT NULL, date_passation DATETIME NOT NULL, score DOUBLE PRECISION NOT NULL, total_points INT NOT NULL, earned_points INT NOT NULL, reponses_etudiant JSON DEFAULT NULL, quiz_id INT NOT NULL, INDEX IDX_2A776B3853CD175 (quiz_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE resultat_quiz ADD CONSTRAINT FK_2A776B3853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data BLOB NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE resultat_quiz DROP FOREIGN KEY FK_2A776B3853CD175');
        $this->addSql('DROP TABLE resultat_quiz');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data BLOB NOT NULL');
    }
}
