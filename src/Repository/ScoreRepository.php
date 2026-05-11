<?php
// src/Repository/ScoreRepository.php
namespace App\Repository;

use App\Entity\Score;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ScoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Score::class);
    }

    /**
     * Recherche générale (pour admin ou ancienne utilisation)
     */
    public function findBySearchAndSort(string $search, string $sortBy = 'dateCorrection', string $order = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('sc')
            ->leftJoin('sc.soumission', 's')
            ->leftJoin('s.evaluation', 'e');

        if (!empty($search)) {
            $qb->where('s.idEtudiant LIKE :search')
               ->orWhere('e.titre LIKE :search')
               ->orWhere('sc.statutCorrection LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $allowedSortFields = ['note', 'dateCorrection', 'statutCorrection'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'dateCorrection';
        }

        $order = strtoupper($order);
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        $qb->orderBy('sc.' . $sortBy, $order);

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les scores d'un étudiant spécifique
     */
    public function findByStudent(string $studentId, string $search = '', string $sortBy = 'dateCorrection', string $order = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('sc')
            ->leftJoin('sc.soumission', 's')
            ->leftJoin('s.evaluation', 'e')
            ->where('s.idEtudiant = :studentId')
            ->setParameter('studentId', $studentId);
        
        if (!empty($search)) {
            $qb->andWhere('e.titre LIKE :search OR sc.commentaire LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        $allowedSortFields = ['note', 'dateCorrection', 'statutCorrection'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'dateCorrection';
        }

        $order = strtoupper($order);
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        $qb->orderBy('sc.' . $sortBy, $order);
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les scores donnés par un enseignant spécifique
     */
    public function findByTeacher(string $teacherId, string $search = '', string $sortBy = 'dateCorrection', string $order = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('sc')
            ->leftJoin('sc.soumission', 's')
            ->leftJoin('s.evaluation', 'e')
            ->where('e.idEnseignant = :teacherId')
            ->setParameter('teacherId', $teacherId);
        
        if (!empty($search)) {
            $qb->andWhere('s.idEtudiant LIKE :search OR e.titre LIKE :search OR sc.commentaire LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        $allowedSortFields = ['note', 'dateCorrection', 'statutCorrection'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'dateCorrection';
        }

        $order = strtoupper($order);
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        $qb->orderBy('sc.' . $sortBy, $order);
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre total de scores pour un enseignant
     */
    public function countByTeacher(string $teacherId): int
    {
        return $this->createQueryBuilder('sc')
            ->select('COUNT(sc.id)')
            ->leftJoin('sc.soumission', 's')
            ->leftJoin('s.evaluation', 'e')
            ->where('e.idEnseignant = :teacherId')
            ->setParameter('teacherId', $teacherId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte le nombre total de scores pour un étudiant
     */
    public function countByStudent(string $studentId): int
    {
        return $this->createQueryBuilder('sc')
            ->select('COUNT(sc.id)')
            ->leftJoin('sc.soumission', 's')
            ->where('s.idEtudiant = :studentId')
            ->setParameter('studentId', $studentId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calcule la moyenne des notes pour un étudiant
     */
    public function getAverageByStudent(string $studentId): ?float
    {
        $result = $this->createQueryBuilder('sc')
            ->select('AVG(sc.note) as moyenne')
            ->leftJoin('sc.soumission', 's')
            ->where('s.idEtudiant = :studentId')
            ->setParameter('studentId', $studentId)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ? (float) $result : null;
    }

    /**
     * Trouve les derniers scores d'un enseignant (pour le dashboard)
     */
    public function findLatestByTeacher(string $teacherId, int $limit = 5): array
    {
        return $this->createQueryBuilder('sc')
            ->leftJoin('sc.soumission', 's')
            ->leftJoin('s.evaluation', 'e')
            ->where('e.idEnseignant = :teacherId')
            ->setParameter('teacherId', $teacherId)
            ->orderBy('sc.dateCorrection', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les derniers scores d'un étudiant (pour le dashboard)
     */
    public function findLatestByStudent(string $studentId, int $limit = 5): array
    {
        return $this->createQueryBuilder('sc')
            ->leftJoin('sc.soumission', 's')
            ->where('s.idEtudiant = :studentId')
            ->setParameter('studentId', $studentId)
            ->orderBy('sc.dateCorrection', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}