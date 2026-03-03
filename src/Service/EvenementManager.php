<?php

namespace App\Service;

use App\Entity\Evenement;

class EvenementManager
{
    public function validate(Evenement $evenement): bool
    {
        // Regle 1 : Titre obligatoire et min 3 caracteres
        if (empty($evenement->getTitre()) || strlen($evenement->getTitre()) < 3) {
            throw new \InvalidArgumentException('Le titre de l\'evenement est obligatoire et doit contenir au moins 3 caracteres');
        }

        // Regle 2 : Type obligatoire (enum non initialise)
        try {
            $evenement->getTypeEvenement();
        } catch (\Error) {
            throw new \InvalidArgumentException('Le type de l\'evenement est obligatoire');
        }

        // Regle 3 : Createur obligatoire
        if ($evenement->getCreateur() === null) {
            throw new \InvalidArgumentException('Le createur de l\'evenement est obligatoire');
        }

        // Regle 4 : Dates obligatoires et coherence (fin > debut)
        if ($evenement->getDateDebut() === null || $evenement->getDateFin() === null) {
            throw new \InvalidArgumentException('Les dates de debut et de fin sont obligatoires');
        }

        if ($evenement->getDateFin() <= $evenement->getDateDebut()) {
            throw new \InvalidArgumentException('La date de fin doit etre posterieure a la date de debut');
        }

        // Regle 5 : Capacite > 0 si renseignee
        if ($evenement->getCapaciteMax() !== null && $evenement->getCapaciteMax() <= 0) {
            throw new \InvalidArgumentException('La capacite maximale doit etre superieure a zero');
        }

        return true;
    }
}
