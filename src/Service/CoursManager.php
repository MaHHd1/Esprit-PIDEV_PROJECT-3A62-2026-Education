<?php

namespace App\Service;

use App\Entity\Cours;

class CoursManager
{
    public function validate(Cours $cours): bool
    {
        // Regle 1 : Code obligatoire et longueur minimale
        if (empty($cours->getCodeCours()) || strlen($cours->getCodeCours()) < 3) {
            throw new \InvalidArgumentException('Le code du cours est obligatoire et doit contenir au moins 3 caracteres');
        }

        // Regle 2 : Titre obligatoire et longueur minimale
        if (empty($cours->getTitre()) || strlen($cours->getTitre()) < 3) {
            throw new \InvalidArgumentException('Le titre du cours est obligatoire et doit contenir au moins 3 caracteres');
        }

        // Regle 3 : Module obligatoire
        if ($cours->getModule() === null) {
            throw new \InvalidArgumentException('Le module est obligatoire');
        }

        // Regle 4 : Credits entre 1 et 500 (si renseignes)
        if ($cours->getCredits() !== null && ($cours->getCredits() < 1 || $cours->getCredits() > 500)) {
            throw new \InvalidArgumentException('Les credits doivent etre entre 1 et 500');
        }

        // Regle 5 : Date fin >= date debut (si renseignees)
        if (
            $cours->getDateDebut() !== null &&
            $cours->getDateFin() !== null &&
            $cours->getDateFin() < $cours->getDateDebut()
        ) {
            throw new \InvalidArgumentException('La date de fin doit etre posterieure ou egale a la date de debut');
        }

        // Regle 6 : Statut valide
        $allowedStatuts = ['brouillon', 'ouvert', 'ferme', 'archive'];
        if (!in_array($cours->getStatut(), $allowedStatuts, true)) {
            throw new \InvalidArgumentException('Le statut du cours est invalide');
        }

        return true;
    }
}
