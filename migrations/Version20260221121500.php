<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221121500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout table cours_temps_passe pour suivi temps reel etudiant/cours.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cours_temps_passe (id INT AUTO_INCREMENT NOT NULL, etudiant_id INT NOT NULL, cours_id INT NOT NULL, secondes INT NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_course_time_student_course (etudiant_id, cours_id), INDEX IDX_154A0942FB8828D6 (etudiant_id), INDEX IDX_154A09427ECF78B0 (cours_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cours_temps_passe ADD CONSTRAINT FK_154A0942FB8828D6 FOREIGN KEY (etudiant_id) REFERENCES Etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours_temps_passe ADD CONSTRAINT FK_154A09427ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cours_temps_passe DROP FOREIGN KEY FK_154A0942FB8828D6');
        $this->addSql('ALTER TABLE cours_temps_passe DROP FOREIGN KEY FK_154A09427ECF78B0');
        $this->addSql('DROP TABLE cours_temps_passe');
    }
}
