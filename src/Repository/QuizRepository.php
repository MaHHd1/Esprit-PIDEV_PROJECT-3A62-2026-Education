<?php

namespace App\Repository;

use App\Entity\Quiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quiz>
 */
class QuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quiz::class);
    }

    public function searchQuiz(string $search): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.titre LIKE :search OR q.description LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('q.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findQuizComplet(int $id): ?Quiz
    {
        return $this->createQueryBuilder('q')
            ->leftJoin('q.questions', 'question')
            ->addSelect('question')
            ->leftJoin('question.reponses', 'reponse')
            ->addSelect('reponse')
            ->andWhere('q.id = :id')
            ->setParameter('id', $id)
            ->orderBy('question.ordre_affichage', 'ASC') // ✅ snake_case car la propriété PHP s'appelle ordre_affichage
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByCreateur(int $idCreateur): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.idCreateur = :idCreateur')
            ->setParameter('idCreateur', $idCreateur)
            ->orderBy('q.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAvailableQuizzes(): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('q')
            ->andWhere('q.dateDebutDisponibilite IS NULL OR q.dateDebutDisponibilite <= :now')
            ->andWhere('q.dateFinDisponibilite IS NULL OR q.dateFinDisponibilite >= :now')
            ->setParameter('now', $now)
            ->orderBy('q.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Retourne les quiz disponibles pour le formulaire de contenu
     * (utilisé dans EnseignantCoursController pour lier un quiz à un contenu de cours)
     */
    public function findForContentForm(int $coursId, int $enseignantId): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.idCreateur = :enseignantId')
            ->setParameter('enseignantId', $enseignantId)
            ->orderBy('q.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}