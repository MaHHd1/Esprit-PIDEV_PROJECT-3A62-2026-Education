<?php

namespace App\Service;

use App\Entity\JournalActivite;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;

class ActivityLogger
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function log(?Utilisateur $actor, string $action, string $entityType, ?int $entityId = null, array $meta = []): void
    {
        $entry = new JournalActivite();
        $entry->setAction($action);
        $entry->setEntiteType($entityType);
        $entry->setEntiteId($entityId);
        $entry->setMeta($meta);

        if ($actor instanceof Utilisateur) {
            $short = strtolower((new \ReflectionClass($actor))->getShortName());
            $entry->setActeurType($short);
            $entry->setActeurId($actor->getId());
        } else {
            $entry->setActeurType('systeme');
            $entry->setActeurId(null);
        }

        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }
}
