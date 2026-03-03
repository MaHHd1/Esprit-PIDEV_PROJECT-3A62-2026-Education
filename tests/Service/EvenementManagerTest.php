<?php

namespace App\Tests\Service;

use App\Entity\Evenement;
use App\Entity\Utilisateur;
use App\Enum\TypeEvenement;
use App\Service\EvenementManager;
use PHPUnit\Framework\TestCase;

class EvenementManagerTest extends TestCase
{
    private function createUtilisateurConcret(): Utilisateur
    {
        $utilisateur = new class extends Utilisateur {};
        $utilisateur->setNom('Doe');
        $utilisateur->setPrenom('John');
        $utilisateur->setEmail('john.doe@example.com');
        $utilisateur->setMotDePasse('Password123');

        return $utilisateur;
    }

    public function testValidEvenement()
    {
        $evenement = new Evenement();
        $evenement->setTitre('Atelier Symfony');
        $evenement->setTypeEvenement(TypeEvenement::ATELIER);
        $evenement->setCreateur($this->createUtilisateurConcret());
        $evenement->setDateDebut(new \DateTime('2026-06-01 10:00:00'));
        $evenement->setDateFin(new \DateTime('2026-06-01 12:00:00'));
        $evenement->setCapaciteMax(30);

        $manager = new EvenementManager();
        $this->assertTrue($manager->validate($evenement));
    }

    public function testEvenementWithShortTitre()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre de l\'evenement est obligatoire et doit contenir au moins 3 caracteres');

        $evenement = new Evenement();
        $evenement->setTitre('AB');
        $evenement->setTypeEvenement(TypeEvenement::ATELIER);
        $evenement->setCreateur($this->createUtilisateurConcret());
        $evenement->setDateDebut(new \DateTime('2026-06-01 10:00:00'));
        $evenement->setDateFin(new \DateTime('2026-06-01 12:00:00'));

        $manager = new EvenementManager();
        $manager->validate($evenement);
    }

    public function testEvenementWithoutType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le type de l\'evenement est obligatoire');

        $evenement = new Evenement();
        $evenement->setTitre('Atelier Symfony');
        $evenement->setCreateur($this->createUtilisateurConcret());
        $evenement->setDateDebut(new \DateTime('2026-06-01 10:00:00'));
        $evenement->setDateFin(new \DateTime('2026-06-01 12:00:00'));

        $manager = new EvenementManager();
        $manager->validate($evenement);
    }

    public function testEvenementWithoutCreateur()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le createur de l\'evenement est obligatoire');

        $evenement = new Evenement();
        $evenement->setTitre('Atelier Symfony');
        $evenement->setTypeEvenement(TypeEvenement::ATELIER);
        $evenement->setDateDebut(new \DateTime('2026-06-01 10:00:00'));
        $evenement->setDateFin(new \DateTime('2026-06-01 12:00:00'));

        $manager = new EvenementManager();
        $manager->validate($evenement);
    }

    public function testEvenementWithInvalidDates()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit etre posterieure a la date de debut');

        $evenement = new Evenement();
        $evenement->setTitre('Atelier Symfony');
        $evenement->setTypeEvenement(TypeEvenement::ATELIER);
        $evenement->setCreateur($this->createUtilisateurConcret());
        $evenement->setDateDebut(new \DateTime('2026-06-01 12:00:00'));
        $evenement->setDateFin(new \DateTime('2026-06-01 10:00:00'));

        $manager = new EvenementManager();
        $manager->validate($evenement);
    }

    public function testEvenementWithInvalidCapacite()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La capacite maximale doit etre superieure a zero');

        $evenement = new Evenement();
        $evenement->setTitre('Atelier Symfony');
        $evenement->setTypeEvenement(TypeEvenement::ATELIER);
        $evenement->setCreateur($this->createUtilisateurConcret());
        $evenement->setDateDebut(new \DateTime('2026-06-01 10:00:00'));
        $evenement->setDateFin(new \DateTime('2026-06-01 12:00:00'));
        $evenement->setCapaciteMax(0);

        $manager = new EvenementManager();
        $manager->validate($evenement);
    }
}
