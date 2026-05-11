<?php

namespace App\Repository;

use App\Entity\ForumDiscussion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class ForumDiscussionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumDiscussion::class);
    }

    /**
     * Pagination + recherche par titre/description
     */
    public function paginate(?string $q, int $page, int $limit = 6): array
    {
        $page = max(1, $page);
        $limit = max(1, min(30, $limit));

        $qb = $this->createQueryBuilder('f')
            ->orderBy('f.derniereActivite', 'DESC');

        if ($q && trim($q) !== '') {
            $qb->andWhere('LOWER(f.titre) LIKE :q OR LOWER(f.description) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower(trim($q)) . '%');
        }

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $paginator = new Paginator($qb);

        $total = count($paginator);
        $pages = (int) ceil($total / $limit);

        return [
            'items' => iterator_to_array($paginator),
            'total' => $total,
            'page' => $page,
            'pages' => max(1, $pages),
            'limit' => $limit,
            'q' => $q ?? '',
        ];
    }
}
