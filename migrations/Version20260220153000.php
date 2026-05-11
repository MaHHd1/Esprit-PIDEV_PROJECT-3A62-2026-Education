<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ JSON ressources pour permettre plusieurs types de contenu dans un seul chapitre.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contenu ADD ressources JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contenu DROP ressources');
    }
}
