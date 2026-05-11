<?php

namespace App\Service;

use App\Entity\ForumDiscussion;

class ForumDiscussionManager
{
    private const TYPES_VALIDES   = ['public', 'prive'];
    private const STATUTS_VALIDES = ['ouvert', 'ferme'];

    public function validate(ForumDiscussion $discussion): bool
    {
        // Règle 1 : le titre est obligatoire
        if (empty(trim($discussion->getTitre()))) {
            throw new \InvalidArgumentException('Le titre de la discussion est obligatoire.');
        }

        // Règle 2 : la description est obligatoire
        if (empty(trim($discussion->getDescription()))) {
            throw new \InvalidArgumentException('La description de la discussion est obligatoire.');
        }

        // Règle 3 : le type doit être 'public' ou 'prive'
        if (!in_array($discussion->getType(), self::TYPES_VALIDES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le type "%s" est invalide. Valeurs acceptées : %s.',
                    $discussion->getType(),
                    implode(', ', self::TYPES_VALIDES)
                )
            );
        }

        // Règle 4 : le statut doit être 'ouvert' ou 'ferme'
        if (!in_array($discussion->getStatut(), self::STATUTS_VALIDES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le statut "%s" est invalide. Valeurs acceptées : %s.',
                    $discussion->getStatut(),
                    implode(', ', self::STATUTS_VALIDES)
                )
            );
        }

        return true;
    }
}