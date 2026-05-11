<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout tables contenu_progression et journal_activite.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE contenu_progression (id INT AUTO_INCREMENT NOT NULL, etudiant_id INT NOT NULL, cours_id INT NOT NULL, contenu_id INT NOT NULL, est_termine TINYINT(1) NOT NULL, date_terminee DATETIME DEFAULT NULL, date_creation DATETIME NOT NULL, UNIQUE INDEX uniq_progress_etudiant_contenu (etudiant_id, contenu_id), INDEX IDX_EC89A570FB8828D6 (etudiant_id), INDEX IDX_EC89A5707ECF78B0 (cours_id), INDEX IDX_EC89A570D5FA8A5A (contenu_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE journal_activite (id INT AUTO_INCREMENT NOT NULL, acteur_type VARCHAR(50) NOT NULL, acteur_id INT DEFAULT NULL, action VARCHAR(120) NOT NULL, entite_type VARCHAR(50) NOT NULL, entite_id INT DEFAULT NULL, meta JSON DEFAULT NULL, date_action DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contenu_progression ADD CONSTRAINT FK_EC89A570FB8828D6 FOREIGN KEY (etudiant_id) REFERENCES Etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contenu_progression ADD CONSTRAINT FK_EC89A5707ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contenu_progression ADD CONSTRAINT FK_EC89A570D5FA8A5A FOREIGN KEY (contenu_id) REFERENCES contenu (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contenu_progression DROP FOREIGN KEY FK_EC89A570FB8828D6');
        $this->addSql('ALTER TABLE contenu_progression DROP FOREIGN KEY FK_EC89A5707ECF78B0');
        $this->addSql('ALTER TABLE contenu_progression DROP FOREIGN KEY FK_EC89A570D5FA8A5A');
        $this->addSql('DROP TABLE contenu_progression');
        $this->addSql('DROP TABLE journal_activite');
    }
}
