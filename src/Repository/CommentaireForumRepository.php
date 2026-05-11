<?php

namespace App\Repository;

use App\Entity\CommentaireForum;
use App\Entity\ForumDiscussion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommentaireForumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentaireForum::class);
    }

    /**
     * Commentaires visibles (utilisateur normal) — cache les supprimés
     */
    public function byForumVisible(ForumDiscussion $forum): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.forum = :f')
            ->setParameter('f', $forum)
            ->andWhere('c.statut != :sup')
            ->setParameter('sup', 'supprime')
            ->orderBy('c.datePublication', 'ASC')
            ->getQuery()->getResult();
    }

    /**
     * ADMIN — tous les commentaires groupés par forum
     * Retourne un tableau indexé par forum_id
     */
    public function allGroupedByForum(array $forums): array
    {
        if (empty($forums)) {
            return [];
        }

        $comments = $this->createQueryBuilder('c')
            ->andWhere('c.forum IN (:forums)')
            ->setParameter('forums', $forums)
            ->orderBy('c.datePublication', 'ASC')
            ->getQuery()->getResult();

        $grouped = [];
        foreach ($comments as $c) {
            $grouped[$c->getForum()->getId()][] = $c;
        }

        return $grouped;
    }
}