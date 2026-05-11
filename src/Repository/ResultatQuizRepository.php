<?php

namespace App\Repository;

use App\Entity\ResultatQuiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ResultatQuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResultatQuiz::class);
    }

    /**
     * Compte le nombre de tentatives d'un étudiant pour un quiz
     */
    public function countTentatives(int $quizId, int $etudiantId): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.quiz = :quizId')
            ->andWhere('r.idEtudiant = :etudiantId')
            ->setParameter('quizId', $quizId)
            ->setParameter('etudiantId', $etudiantId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère tous les résultats d'un étudiant pour un quiz (historique)
     */
    public function findByEtudiantAndQuiz(int $quizId, int $etudiantId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.quiz = :quizId')
            ->andWhere('r.idEtudiant = :etudiantId')
            ->setParameter('quizId', $quizId)
            ->setParameter('etudiantId', $etudiantId)
            ->orderBy('r.datePassation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les résultats d'un étudiant (tous quiz)
     */
    public function findAllByEtudiant(int $etudiantId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.idEtudiant = :etudiantId')
            ->setParameter('etudiantId', $etudiantId)
            ->orderBy('r.datePassation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les résultats pour un quiz (vue enseignant)
     */
    public function findAllByQuiz(int $quizId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.quiz = :quizId')
            ->setParameter('quizId', $quizId)
            ->orderBy('r.datePassation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Meilleur score d'un étudiant pour un quiz
     */
    public function findBestScore(int $quizId, int $etudiantId): ?ResultatQuiz
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.quiz = :quizId')
            ->andWhere('r.idEtudiant = :etudiantId')
            ->setParameter('quizId', $quizId)
            ->setParameter('etudiantId', $etudiantId)
            ->orderBy('r.score', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}