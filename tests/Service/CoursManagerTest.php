<?php

namespace App\Tests\Service;

use App\Entity\Cours;
use App\Entity\Module;
use App\Service\CoursManager;
use PHPUnit\Framework\TestCase;

class CoursManagerTest extends TestCase
{
    public function testValidCours()
    {
        $cours = new Cours();
        $cours->setCodeCours('CS-101');
        $cours->setTitre('Introduction a PHP');
        $cours->setModule(new Module());
        $cours->setCredits(4);
        $cours->setDateDebut(new \DateTime('2026-01-01'));
        $cours->setDateFin(new \DateTime('2026-06-01'));
        $cours->setStatut('brouillon');

        $manager = new CoursManager();
        $this->assertTrue($manager->validate($cours));
    }

    public function testCoursWithShortCode()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le code du cours est obligatoire et doit contenir au moins 3 caracteres');

        $cours = new Cours();
        $cours->setCodeCours('C1');
        $cours->setTitre('Introduction a PHP');
        $cours->setModule(new Module());

        $manager = new CoursManager();
        $manager->validate($cours);
    }

    public function testCoursWithEmptyTitre()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre du cours est obligatoire et doit contenir au moins 3 caracteres');

        $cours = new Cours();
        $cours->setCodeCours('CS-101');
        $cours->setTitre('');
        $cours->setModule(new Module());

        $manager = new CoursManager();
        $manager->validate($cours);
    }

    public function testCoursWithoutModule()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le module est obligatoire');

        $cours = new Cours();
        $cours->setCodeCours('CS-101');
        $cours->setTitre('Introduction a PHP');

        $manager = new CoursManager();
        $manager->validate($cours);
    }

    public function testCoursWithInvalidCredits()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Les credits doivent etre entre 1 et 500');

        $cours = new Cours();
        $cours->setCodeCours('CS-101');
        $cours->setTitre('Introduction a PHP');
        $cours->setModule(new Module());
        $cours->setCredits(0);

        $manager = new CoursManager();
        $manager->validate($cours);
    }

    public function testCoursWithInvalidDates()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit etre posterieure ou egale a la date de debut');

        $cours = new Cours();
        $cours->setCodeCours('CS-101');
        $cours->setTitre('Introduction a PHP');
        $cours->setModule(new Module());
        $cours->setDateDebut(new \DateTime('2026-06-01'));
        $cours->setDateFin(new \DateTime('2026-01-01'));

        $manager = new CoursManager();
        $manager->validate($cours);
    }

    public function testCoursWithInvalidStatut()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut du cours est invalide');

        $cours = new Cours();
        $cours->setCodeCours('CS-101');
        $cours->setTitre('Introduction a PHP');
        $cours->setModule(new Module());
        $cours->setStatut('inconnu');

        $manager = new CoursManager();
        $manager->validate($cours);
    }
}
