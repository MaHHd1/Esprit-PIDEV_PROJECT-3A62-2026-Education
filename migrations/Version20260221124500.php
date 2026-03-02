<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout table cours_vue pour vues uniques etudiant/cours.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cours_vue (id INT AUTO_INCREMENT NOT NULL, etudiant_id INT NOT NULL, cours_id INT NOT NULL, date_vue DATETIME NOT NULL, UNIQUE INDEX uniq_cours_vue_etudiant_cours (etudiant_id, cours_id), INDEX IDX_DBCB6B8CFB8828D6 (etudiant_id), INDEX IDX_DBCB6B8C7ECF78B0 (cours_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cours_vue ADD CONSTRAINT FK_DBCB6B8CFB8828D6 FOREIGN KEY (etudiant_id) REFERENCES Etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours_vue ADD CONSTRAINT FK_DBCB6B8C7ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cours_vue DROP FOREIGN KEY FK_DBCB6B8CFB8828D6');
        $this->addSql('ALTER TABLE cours_vue DROP FOREIGN KEY FK_DBCB6B8C7ECF78B0');
        $this->addSql('DROP TABLE cours_vue');
    }
}
