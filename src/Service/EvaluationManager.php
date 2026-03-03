<?php

namespace App\Service;

use App\Entity\Evaluation;

class EvaluationManager
{
    public function validate(Evaluation $evaluation): bool
    {
        // Règle 1 : Le titre est obligatoire et doit avoir au moins 3 caractères
        if (empty($evaluation->getTitre()) || strlen($evaluation->getTitre()) < 3) {
            throw new \InvalidArgumentException('Le titre est obligatoire et doit contenir au moins 3 caractères');
        }

        // Règle 2 : La note maximale doit être un nombre strictement positif
        if ($evaluation->getNoteMax() === null || (float)$evaluation->getNoteMax() <= 0) {
            throw new \InvalidArgumentException('La note maximale doit être supérieure à zéro');
        }

        // Règle 3 : La date limite doit être postérieure à la date de création
        if (
            $evaluation->getDateLimite() !== null &&
            $evaluation->getDateCreation() !== null &&
            $evaluation->getDateLimite() <= $evaluation->getDateCreation()
        ) {
            throw new \InvalidArgumentException('La date limite doit être postérieure à la date de création');
        }

        return true;
    }
}