<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222200921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE etudiant_cours (etudiant_id INT NOT NULL, cours_id INT NOT NULL, INDEX IDX_82F0A080DDEAB1A3 (etudiant_id), INDEX IDX_82F0A0807ECF78B0 (cours_id), PRIMARY KEY (etudiant_id, cours_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contenu (id INT AUTO_INCREMENT NOT NULL, type_contenu VARCHAR(50) NOT NULL, titre VARCHAR(255) NOT NULL, url_contenu VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, duree INT DEFAULT NULL, ordre_affichage INT NOT NULL, est_public TINYINT NOT NULL, date_ajout DATETIME NOT NULL, nombre_vues INT NOT NULL, format VARCHAR(20) DEFAULT NULL, ressources JSON DEFAULT NULL, cours_id INT NOT NULL, INDEX IDX_89C2003F7ECF78B0 (cours_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contenu_progression (id INT AUTO_INCREMENT NOT NULL, est_termine TINYINT NOT NULL, date_terminee DATETIME DEFAULT NULL, date_creation DATETIME NOT NULL, etudiant_id INT NOT NULL, cours_id INT NOT NULL, contenu_id INT NOT NULL, INDEX IDX_5B0E2370DDEAB1A3 (etudiant_id), INDEX IDX_5B0E23707ECF78B0 (cours_id), INDEX IDX_5B0E23703C1CC488 (contenu_id), UNIQUE INDEX uniq_progress_etudiant_contenu (etudiant_id, contenu_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cours (id INT AUTO_INCREMENT NOT NULL, code_cours VARCHAR(50) NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, niveau VARCHAR(50) DEFAULT NULL, credits INT DEFAULT NULL, langue VARCHAR(50) DEFAULT NULL, date_creation DATETIME NOT NULL, date_debut DATE DEFAULT NULL, date_fin DATE DEFAULT NULL, statut VARCHAR(20) NOT NULL, image_cours_url VARCHAR(255) DEFAULT NULL, prerequis JSON DEFAULT NULL, module_id INT NOT NULL, UNIQUE INDEX UNIQ_FDCA8C9C119F95D0 (code_cours), INDEX IDX_FDCA8C9CAFC2B591 (module_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cours_enseignant (cours_id INT NOT NULL, enseignant_id INT NOT NULL, INDEX IDX_845FDD887ECF78B0 (cours_id), INDEX IDX_845FDD88E455FCC0 (enseignant_id), PRIMARY KEY (cours_id, enseignant_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cours_temps_passe (id INT AUTO_INCREMENT NOT NULL, secondes INT NOT NULL, updated_at DATETIME NOT NULL, etudiant_id INT NOT NULL, cours_id INT NOT NULL, INDEX IDX_6414A182DDEAB1A3 (etudiant_id), INDEX IDX_6414A1827ECF78B0 (cours_id), UNIQUE INDEX uniq_course_time_student_course (etudiant_id, cours_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cours_vue (id INT AUTO_INCREMENT NOT NULL, date_vue DATETIME NOT NULL, etudiant_id INT NOT NULL, cours_id INT NOT NULL, INDEX IDX_6066C1F3DDEAB1A3 (etudiant_id), INDEX IDX_6066C1F37ECF78B0 (cours_id), UNIQUE INDEX uniq_cours_vue_etudiant_cours (etudiant_id, cours_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE journal_activite (id INT AUTO_INCREMENT NOT NULL, acteur_type VARCHAR(50) NOT NULL, acteur_id INT DEFAULT NULL, action VARCHAR(120) NOT NULL, entite_type VARCHAR(50) NOT NULL, entite_id INT DEFAULT NULL, meta JSON DEFAULT NULL, date_action DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE module (id INT AUTO_INCREMENT NOT NULL, titre_module VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, ordre_affichage INT NOT NULL, objectifs_apprentissage LONGTEXT DEFAULT NULL, duree_estimee_heures INT DEFAULT NULL, date_publication DATETIME DEFAULT NULL, statut VARCHAR(20) NOT NULL, ressources_complementaires JSON DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE etudiant_cours ADD CONSTRAINT FK_82F0A080DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES Etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE etudiant_cours ADD CONSTRAINT FK_82F0A0807ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contenu ADD CONSTRAINT FK_89C2003F7ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
        $this->addSql('ALTER TABLE contenu_progression ADD CONSTRAINT FK_5B0E2370DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES Etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contenu_progression ADD CONSTRAINT FK_5B0E23707ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contenu_progression ADD CONSTRAINT FK_5B0E23703C1CC488 FOREIGN KEY (contenu_id) REFERENCES contenu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours ADD CONSTRAINT FK_FDCA8C9CAFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');
        $this->addSql('ALTER TABLE cours_enseignant ADD CONSTRAINT FK_845FDD887ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours_enseignant ADD CONSTRAINT FK_845FDD88E455FCC0 FOREIGN KEY (enseignant_id) REFERENCES Enseignant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours_temps_passe ADD CONSTRAINT FK_6414A182DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES Etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours_temps_passe ADD CONSTRAINT FK_6414A1827ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours_vue ADD CONSTRAINT FK_6066C1F3DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES Etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours_vue ADD CONSTRAINT FK_6066C1F37ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE resultat_quiz DROP FOREIGN KEY `FK_2A776B3853CD175`');
        $this->addSql('DROP TABLE resultat_quiz');
        $this->addSql('ALTER TABLE forum_discussion DROP piece_jointe_url');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data BLOB NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE resultat_quiz (id INT AUTO_INCREMENT NOT NULL, id_etudiant INT NOT NULL, date_passation DATETIME NOT NULL, score DOUBLE PRECISION NOT NULL, total_points INT NOT NULL, earned_points INT NOT NULL, reponses_etudiant JSON DEFAULT NULL, quiz_id INT NOT NULL, INDEX IDX_2A776B3853CD175 (quiz_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE resultat_quiz ADD CONSTRAINT `FK_2A776B3853CD175` FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE etudiant_cours DROP FOREIGN KEY FK_82F0A080DDEAB1A3');
        $this->addSql('ALTER TABLE etudiant_cours DROP FOREIGN KEY FK_82F0A0807ECF78B0');
        $this->addSql('ALTER TABLE contenu DROP FOREIGN KEY FK_89C2003F7ECF78B0');
        $this->addSql('ALTER TABLE contenu_progression DROP FOREIGN KEY FK_5B0E2370DDEAB1A3');
        $this->addSql('ALTER TABLE contenu_progression DROP FOREIGN KEY FK_5B0E23707ECF78B0');
        $this->addSql('ALTER TABLE contenu_progression DROP FOREIGN KEY FK_5B0E23703C1CC488');
        $this->addSql('ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9CAFC2B591');
        $this->addSql('ALTER TABLE cours_enseignant DROP FOREIGN KEY FK_845FDD887ECF78B0');
        $this->addSql('ALTER TABLE cours_enseignant DROP FOREIGN KEY FK_845FDD88E455FCC0');
        $this->addSql('ALTER TABLE cours_temps_passe DROP FOREIGN KEY FK_6414A182DDEAB1A3');
        $this->addSql('ALTER TABLE cours_temps_passe DROP FOREIGN KEY FK_6414A1827ECF78B0');
        $this->addSql('ALTER TABLE cours_vue DROP FOREIGN KEY FK_6066C1F3DDEAB1A3');
        $this->addSql('ALTER TABLE cours_vue DROP FOREIGN KEY FK_6066C1F37ECF78B0');
        $this->addSql('DROP TABLE etudiant_cours');
        $this->addSql('DROP TABLE contenu');
        $this->addSql('DROP TABLE contenu_progression');
        $this->addSql('DROP TABLE cours');
        $this->addSql('DROP TABLE cours_enseignant');
        $this->addSql('DROP TABLE cours_temps_passe');
        $this->addSql('DROP TABLE cours_vue');
        $this->addSql('DROP TABLE journal_activite');
        $this->addSql('DROP TABLE module');
        $this->addSql('ALTER TABLE forum_discussion ADD piece_jointe_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data BLOB NOT NULL');
    }
}
