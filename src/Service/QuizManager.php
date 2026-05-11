<?php
namespace App\Service;

use App\Entity\Quiz;

class QuizManager
{
    public function validate(Quiz $quiz): bool
    {
        // Règle 1 : Titre obligatoire
        if (empty($quiz->getTitre())) {
            throw new \InvalidArgumentException('Le titre du quiz est obligatoire');
        }

        // Règle 2 : Tentatives > 0
        if ($quiz->getNombreTentativesAutorisees() !== null 
            && $quiz->getNombreTentativesAutorisees() <= 0) {
            throw new \InvalidArgumentException('Le nombre de tentatives doit être supérieur à zéro');
        }

        // Règle 3 : Date fin > date début
        if ($quiz->getDateDebutDisponibilite() !== null 
            && $quiz->getDateFinDisponibilite() !== null
            && $quiz->getDateFinDisponibilite() <= $quiz->getDateDebutDisponibilite()) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début');
        }

        return true;
    }
}