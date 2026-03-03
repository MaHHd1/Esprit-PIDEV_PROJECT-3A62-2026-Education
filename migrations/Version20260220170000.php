<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la table d inscription etudiant_cours.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE etudiant_cours (etudiant_id INT NOT NULL, cours_id INT NOT NULL, INDEX IDX_A8E6BB8DFB8828D6 (etudiant_id), INDEX IDX_A8E6BB8D7ECF78B0 (cours_id), PRIMARY KEY(etudiant_id, cours_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE etudiant_cours ADD CONSTRAINT FK_A8E6BB8DFB8828D6 FOREIGN KEY (etudiant_id) REFERENCES Etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE etudiant_cours ADD CONSTRAINT FK_A8E6BB8D7ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE etudiant_cours DROP FOREIGN KEY FK_A8E6BB8DFB8828D6');
        $this->addSql('ALTER TABLE etudiant_cours DROP FOREIGN KEY FK_A8E6BB8D7ECF78B0');
        $this->addSql('DROP TABLE etudiant_cours');
    }
}
