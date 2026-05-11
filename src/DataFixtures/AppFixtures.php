<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Administrateur;
use App\Entity\Enseignant;
use App\Entity\Etudiant;
use App\Entity\Cours;
use App\Entity\Module;
use App\Entity\Contenu;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Administrateur
        $admin = new Administrateur();
        $admin->setNom('Admin')
              ->setPrenom('Super')
              ->setEmail('admin@example.test')
              ->setMotDePasse(password_hash('Admin1234', PASSWORD_BCRYPT))
              ->setDepartement('Informatique')
              ->setFonction('Administrateur Système')
              ->setTelephone('+33100000000')
        ;
        $manager->persist($admin);

        // Enseignants
        $ens1 = new Enseignant();
        $ens1->setNom('Dupont')
             ->setPrenom('Alice')
             ->setEmail('alice.dupont@example.test')
             ->setMotDePasse(password_hash('Teacher123', PASSWORD_BCRYPT))
             ->setMatriculeEnseignant('ENS-001')
             ->setDiplome('Master')
             ->setSpecialite('Mathématiques')
             ->setAnneesExperience(6)
             ->setTypeContrat('CDI')
        ;
        $manager->persist($ens1);

        $ens2 = new Enseignant();
        $ens2->setNom('Martin')
             ->setPrenom('Bob')
             ->setEmail('bob.martin@example.test')
             ->setMotDePasse(password_hash('Teacher123', PASSWORD_BCRYPT))
             ->setMatriculeEnseignant('ENS-002')
             ->setDiplome('Doctorat')
             ->setSpecialite('Informatique')
             ->setAnneesExperience(8)
             ->setTypeContrat('CDI')
        ;
        $manager->persist($ens2);

        // Étudiants
        for ($i = 1; $i <= 8; $i++) {
            $e = new Etudiant();
            $e->setNom('Etudiant' . $i)
              ->setPrenom('E' . $i)
              ->setEmail('etudiant' . $i . '@example.test')
              ->setMotDePasse(password_hash('Student123', PASSWORD_BCRYPT))
              ->setMatricule('STU-' . str_pad($i, 4, '0', STR_PAD_LEFT))
              ->setNiveauEtude('Licence 1')
              ->setSpecialisation('Génie Logiciel')
              ->setDateNaissance(new \DateTime('-20 years'))
              ->setTelephone('+3360000000' . $i)
              ->setAdresse('123 Rue de Test, Ville')
            ;
            $manager->persist($e);
        }

        // Modules, Cours, Contenus
        $moduleData = [
            ['titre' => 'Mathématiques I', 'desc' => 'Bases de l\'analyse.'],
            ['titre' => 'Introduction à la programmation', 'desc' => 'Algorithmes et logique.'],
            ['titre' => 'Physique I', 'desc' => 'Mécanique classique.'],
        ];

        foreach ($moduleData as $idx => $mdata) {
            $module = new Module();
            $module->setTitreModule($mdata['titre'])
                   ->setDescription($mdata['desc'])
                   ->setOrdreAffichage($idx + 1)
            ;
            $manager->persist($module);

            // 3 Cours par Module
            for ($c = 1; $c <= 3; $c++) {
                $cours = new Cours();
                $cours->setModule($module)
                      ->setCodeCours('COUR-' . ($idx + 1) . '-' . $c)
                      ->setTitre($mdata['titre'] . ' - Cours ' . $c)
                      ->setDescription('Contenu du cours ' . $c . ' du module')
                      ->addEnseignant($c % 2 === 0 ? $ens1 : $ens2)
                      ->setNiveau('Licence 1')
                      ->setCredits(1)
                      ->setLangue('Français')
                      ->setStatut('ouvert')
                ;
                $manager->persist($cours);

                // 2 Contenus par Cours
                for ($cnt = 1; $cnt <= 2; $cnt++) {
                    if ($cnt === 1) {
                        // Contenu texte public
                        $contenu = new Contenu();
                        $contenu->setCours($cours)
                               ->setTypeContenu('texte')
                               ->setTitre('Texte ' . $cnt . ' - ' . $cours->getTitre())
                               ->setDescription('Contenu textuel du cours')
                               ->setOrdreAffichage($cnt)
                               ->setEstPublic(true)
                        ;
                        $manager->persist($contenu);
                    } else {
                        // Contenu vidéo premium
                        $contenu = new Contenu();
                        $contenu->setCours($cours)
                               ->setTypeContenu('video')
                               ->setTitre('Vidéo ' . $cnt . ' - ' . $cours->getTitre())
                               ->setUrlContenu('https://cdn.example.test/videos/' . $cours->getCodeCours() . '_v' . $cnt . '.mp4')
                               ->setDuree(15)
                               ->setOrdreAffichage($cnt)
                               ->setEstPublic(false)
                        ;
                        $manager->persist($contenu);
                    }
                }
            }
        }

        $manager->flush();
    }
}
