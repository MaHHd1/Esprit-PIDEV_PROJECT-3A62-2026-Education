<?php

namespace App\Repository;

use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    public function findByQuiz(int $idQuiz): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.quiz = :idQuiz')
            ->setParameter('idQuiz', $idQuiz)
            ->orderBy('q.ordre_affichage', 'ASC') // ✅ underscore ici
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter le nombre de questions par type pour un quiz
     */
    public function countByTypeQuiz(int $idQuiz): array
    {
        return $this->createQueryBuilder('q')
            ->select('q.type_quiz, COUNT(q.id) as nb')
            ->andWhere('q.quiz = :idQuiz')
            ->setParameter('idQuiz', $idQuiz)
            ->groupBy('q.type_quiz')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtenir le prochain ordre d'affichage pour une question
     */
    public function getNextOrdreAffichage(int $idQuiz): int
    {
        $result = $this->createQueryBuilder('q')
            ->select('MAX(q.ordre_affichage) as maxOrdre') // ✅ underscore ici
            ->andWhere('q.quiz = :idQuiz')
            ->setParameter('idQuiz', $idQuiz)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? $result + 1 : 1;
    }

    /**
     * Total points du quiz
     */
    public function getTotalPoints(int $idQuiz): int
    {
        return (int) $this->createQueryBuilder('q')
            ->select('SUM(q.points)')
            ->andWhere('q.quiz = :idQuiz')
            ->setParameter('idQuiz', $idQuiz)
            ->getQuery()
            ->getSingleScalarResult();
            
    }
}
