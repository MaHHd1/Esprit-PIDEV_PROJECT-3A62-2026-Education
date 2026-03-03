<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260220103613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commentaire_forum (id_commentaire INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_publication DATETIME NOT NULL, date_modification DATETIME DEFAULT NULL, likes INT NOT NULL, dislikes INT NOT NULL, signalements INT NOT NULL, statut VARCHAR(20) NOT NULL, est_modifie TINYINT NOT NULL, nb_reponses INT NOT NULL, id_forum INT NOT NULL, id_utilisateur INT NOT NULL, id_parent INT DEFAULT NULL, INDEX IDX_A776D16BAEFFFD (id_forum), INDEX IDX_A776D150EAE44 (id_utilisateur), INDEX IDX_A776D11BB9D5A2 (id_parent), PRIMARY KEY (id_commentaire)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE evaluation (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, type_evaluation VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, id_cours VARCHAR(100) NOT NULL, id_enseignant VARCHAR(100) NOT NULL, date_creation DATETIME NOT NULL, date_limite DATETIME NOT NULL, note_max NUMERIC(5, 2) NOT NULL, mode_remise VARCHAR(50) NOT NULL, statut VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE evenement (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type_evenement VARCHAR(50) NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, lieu VARCHAR(255) DEFAULT NULL, capacite_max INT DEFAULT NULL, statut VARCHAR(50) NOT NULL, visibilite VARCHAR(50) NOT NULL, createur_id INT NOT NULL, INDEX IDX_B26681E73A201E5 (createur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE forum_discussion (id_forum INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, id_cours INT DEFAULT NULL, date_creation DATETIME NOT NULL, type VARCHAR(20) NOT NULL, statut VARCHAR(20) NOT NULL, nombre_vues INT NOT NULL, derniere_activite DATETIME DEFAULT NULL, tags JSON DEFAULT NULL, regles_moderation LONGTEXT DEFAULT NULL, image_couverture_url VARCHAR(255) DEFAULT NULL, likes INT NOT NULL, dislikes INT NOT NULL, signalements INT NOT NULL, est_modifie TINYINT NOT NULL, date_modification DATETIME DEFAULT NULL, id_createur INT NOT NULL, INDEX IDX_428F444AAA033611 (id_createur), PRIMARY KEY (id_forum)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, objet VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, date_envoi DATETIME NOT NULL, date_lecture DATETIME DEFAULT NULL, statut VARCHAR(20) NOT NULL, priorite VARCHAR(20) NOT NULL, piece_jointe_url VARCHAR(255) DEFAULT NULL, categorie VARCHAR(20) NOT NULL, est_archive_expediteur TINYINT NOT NULL, est_archive_destinataire TINYINT NOT NULL, est_supprime_expediteur TINYINT NOT NULL, est_supprime_destinataire TINYINT NOT NULL, expediteur_id INT NOT NULL, destinataire_id INT NOT NULL, parent_id INT DEFAULT NULL, INDEX IDX_B6BD307F10335F61 (expediteur_id), INDEX IDX_B6BD307FA4F84F6E (destinataire_id), INDEX IDX_B6BD307F727ACA70 (parent_id), INDEX idx_message_date_envoi (date_envoi), INDEX idx_message_statut (statut), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE participation_evenement (id INT AUTO_INCREMENT NOT NULL, statut VARCHAR(50) NOT NULL, date_inscription DATETIME NOT NULL, heure_arrivee DATETIME DEFAULT NULL, heure_depart DATETIME DEFAULT NULL, feedback_note INT DEFAULT NULL, feedback_commentaire LONGTEXT DEFAULT NULL, evenement_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_65A14675FD02F13 (evenement_id), INDEX IDX_65A14675FB88E14F (utilisateur_id), UNIQUE INDEX unique_participation (evenement_id, utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, id_cours INT DEFAULT NULL, type_quiz VARCHAR(255) DEFAULT NULL, date_creation DATETIME DEFAULT NULL, ordre_affichage INT DEFAULT NULL, texte LONGTEXT DEFAULT NULL, points INT DEFAULT NULL, type_question VARCHAR(255) DEFAULT NULL, metadata JSON DEFAULT NULL, explication_reponse LONGTEXT DEFAULT NULL, createur_id INT DEFAULT NULL, quiz_id INT NOT NULL, INDEX IDX_B6F7494E73A201E5 (createur_id), INDEX IDX_B6F7494E853CD175 (quiz_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quiz (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type_quiz VARCHAR(50) DEFAULT NULL, duree_minutes INT DEFAULT NULL, nombre_tentatives_autorisees INT DEFAULT NULL, difficulte_moyenne DOUBLE PRECISION DEFAULT NULL, instructions LONGTEXT DEFAULT NULL, date_creation DATETIME DEFAULT NULL, date_debut_disponibilite DATETIME DEFAULT NULL, date_fin_disponibilite DATETIME DEFAULT NULL, afficher_correction_apres VARCHAR(255) DEFAULT NULL, id_createur INT DEFAULT NULL, id_cours INT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reponse (id INT AUTO_INCREMENT NOT NULL, texte_reponse LONGTEXT DEFAULT NULL, est_correcte TINYINT DEFAULT NULL, ordre_affichage INT DEFAULT NULL, pourcentage_points NUMERIC(5, 2) DEFAULT NULL, feedback_specifique LONGTEXT DEFAULT NULL, media_url VARCHAR(500) DEFAULT NULL, question_id INT NOT NULL, INDEX IDX_5FB6DEC71E27F6BF (question_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE score (id INT AUTO_INCREMENT NOT NULL, note NUMERIC(5, 2) NOT NULL, note_sur NUMERIC(5, 2) NOT NULL, commentaire_enseignant LONGTEXT DEFAULT NULL, date_correction DATETIME NOT NULL, statut_correction VARCHAR(50) NOT NULL, soumission_id INT NOT NULL, UNIQUE INDEX UNIQ_32993751801F9DE6 (soumission_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sessions (sess_id VARCHAR(128) NOT NULL, sess_data BLOB NOT NULL, sess_lifetime INT UNSIGNED NOT NULL, sess_time INT UNSIGNED NOT NULL, INDEX sessions_sess_lifetime_idx (sess_lifetime), PRIMARY KEY (sess_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE soumission (id INT AUTO_INCREMENT NOT NULL, id_etudiant VARCHAR(100) NOT NULL, fichier_soumission_url VARCHAR(255) DEFAULT NULL, commentaire_etudiant LONGTEXT DEFAULT NULL, date_soumission DATETIME NOT NULL, statut VARCHAR(50) NOT NULL, evaluation_id INT NOT NULL, INDEX IDX_9495AA2E456C5646 (evaluation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE commentaire_forum ADD CONSTRAINT FK_A776D16BAEFFFD FOREIGN KEY (id_forum) REFERENCES forum_discussion (id_forum) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire_forum ADD CONSTRAINT FK_A776D150EAE44 FOREIGN KEY (id_utilisateur) REFERENCES Utilisateur (id)');
        $this->addSql('ALTER TABLE commentaire_forum ADD CONSTRAINT FK_A776D11BB9D5A2 FOREIGN KEY (id_parent) REFERENCES commentaire_forum (id_commentaire) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681E73A201E5 FOREIGN KEY (createur_id) REFERENCES Utilisateur (id)');
        $this->addSql('ALTER TABLE forum_discussion ADD CONSTRAINT FK_428F444AAA033611 FOREIGN KEY (id_createur) REFERENCES Utilisateur (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F10335F61 FOREIGN KEY (expediteur_id) REFERENCES Utilisateur (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FA4F84F6E FOREIGN KEY (destinataire_id) REFERENCES Utilisateur (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F727ACA70 FOREIGN KEY (parent_id) REFERENCES message (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE participation_evenement ADD CONSTRAINT FK_65A14675FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participation_evenement ADD CONSTRAINT FK_65A14675FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E73A201E5 FOREIGN KEY (createur_id) REFERENCES Utilisateur (id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC71E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE score ADD CONSTRAINT FK_32993751801F9DE6 FOREIGN KEY (soumission_id) REFERENCES soumission (id)');
        $this->addSql('ALTER TABLE soumission ADD CONSTRAINT FK_9495AA2E456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id)');
        $this->addSql('ALTER TABLE contenu DROP FOREIGN KEY `FK_89C2003FAFC2B591`');
        $this->addSql('DROP INDEX IDX_89C2003FAFC2B591 ON contenu');
        $this->addSql('ALTER TABLE contenu CHANGE module_id cours_id INT NOT NULL');
        $this->addSql('ALTER TABLE contenu ADD CONSTRAINT FK_89C2003F7ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
        $this->addSql('CREATE INDEX IDX_89C2003F7ECF78B0 ON contenu (cours_id)');
        $this->addSql('ALTER TABLE cours ADD module_id INT NOT NULL');
        $this->addSql('ALTER TABLE cours ADD CONSTRAINT FK_FDCA8C9CAFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');
        $this->addSql('CREATE INDEX IDX_FDCA8C9CAFC2B591 ON cours (module_id)');
        $this->addSql('ALTER TABLE module DROP FOREIGN KEY `FK_C2426287ECF78B0`');
        $this->addSql('DROP INDEX IDX_C2426287ECF78B0 ON module');
        $this->addSql('ALTER TABLE module DROP cours_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire_forum DROP FOREIGN KEY FK_A776D16BAEFFFD');
        $this->addSql('ALTER TABLE commentaire_forum DROP FOREIGN KEY FK_A776D150EAE44');
        $this->addSql('ALTER TABLE commentaire_forum DROP FOREIGN KEY FK_A776D11BB9D5A2');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681E73A201E5');
        $this->addSql('ALTER TABLE forum_discussion DROP FOREIGN KEY FK_428F444AAA033611');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F10335F61');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FA4F84F6E');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F727ACA70');
        $this->addSql('ALTER TABLE participation_evenement DROP FOREIGN KEY FK_65A14675FD02F13');
        $this->addSql('ALTER TABLE participation_evenement DROP FOREIGN KEY FK_65A14675FB88E14F');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E73A201E5');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E853CD175');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC71E27F6BF');
        $this->addSql('ALTER TABLE score DROP FOREIGN KEY FK_32993751801F9DE6');
        $this->addSql('ALTER TABLE soumission DROP FOREIGN KEY FK_9495AA2E456C5646');
        $this->addSql('DROP TABLE commentaire_forum');
        $this->addSql('DROP TABLE evaluation');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE forum_discussion');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE participation_evenement');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE reponse');
        $this->addSql('DROP TABLE score');
        $this->addSql('DROP TABLE sessions');
        $this->addSql('DROP TABLE soumission');
        $this->addSql('ALTER TABLE contenu DROP FOREIGN KEY FK_89C2003F7ECF78B0');
        $this->addSql('DROP INDEX IDX_89C2003F7ECF78B0 ON contenu');
        $this->addSql('ALTER TABLE contenu CHANGE cours_id module_id INT NOT NULL');
        $this->addSql('ALTER TABLE contenu ADD CONSTRAINT `FK_89C2003FAFC2B591` FOREIGN KEY (module_id) REFERENCES module (id)');
        $this->addSql('CREATE INDEX IDX_89C2003FAFC2B591 ON contenu (module_id)');
        $this->addSql('ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9CAFC2B591');
        $this->addSql('DROP INDEX IDX_FDCA8C9CAFC2B591 ON cours');
        $this->addSql('ALTER TABLE cours DROP module_id');
        $this->addSql('ALTER TABLE module ADD cours_id INT NOT NULL');
        $this->addSql('ALTER TABLE module ADD CONSTRAINT `FK_C2426287ECF78B0` FOREIGN KEY (cours_id) REFERENCES cours (id)');
        $this->addSql('CREATE INDEX IDX_C2426287ECF78B0 ON module (cours_id)');
    }
}
