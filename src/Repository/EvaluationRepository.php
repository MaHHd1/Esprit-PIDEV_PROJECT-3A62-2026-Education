<?php
// src/Repository/EvaluationRepository.php
namespace App\Repository;

use App\Entity\Evaluation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evaluation::class);
    }

    public function findBySearchAndSort(string $search, string $sortBy = 'dateCreation', string $order = 'DESC', ?int $enseignantId = null, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('e');

        // Filter by teacher if provided
        if ($enseignantId !== null) {
            $qb->andWhere('e.idEnseignant = :enseignantId')
               ->setParameter('enseignantId', $enseignantId);
        }

        if (!empty($search)) {
            $qb->andWhere('e.titre LIKE :search')
               ->orWhere('e.typeEvaluation LIKE :search')
               ->orWhere('e.statut LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Validation du champ de tri
        $allowedSortFields = ['titre', 'typeEvaluation', 'dateCreation', 'dateLimite', 'noteMax', 'statut'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'dateCreation';
        }

        // Validation de l'ordre
        $order = strtoupper($order);
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        $qb->orderBy('e.' . $sortBy, $order);

        // Add pagination
        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function countBySearchAndSort(string $search, ?int $enseignantId = null): int
    {
        $qb = $this->createQueryBuilder('e')
                   ->select('COUNT(e.id)');

        // Filter by teacher if provided
        if ($enseignantId !== null) {
            $qb->andWhere('e.idEnseignant = :enseignantId')
               ->setParameter('enseignantId', $enseignantId);
        }

        if (!empty($search)) {
            $qb->andWhere('e.titre LIKE :search')
               ->orWhere('e.typeEvaluation LIKE :search')
               ->orWhere('e.statut LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}