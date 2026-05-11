<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    // Gardez vos méthodes existantes
    public function countByStatut(string $statut): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.statut = :statut')
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.dateDebut', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // AJOUTEZ CES NOUVELLES MÉTHODES :

    /**
     * Recherche et tri des événements
     */
    public function search(array $criteria = []): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.createur', 'c');

        // Recherche par texte
        if (!empty($criteria['search'])) {
            $qb->andWhere('e.titre LIKE :search OR e.description LIKE :search OR e.lieu LIKE :search OR c.email LIKE :search')
               ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        // Filtre par type
        if (!empty($criteria['type'])) {
            $qb->andWhere('e.typeEvenement = :type')
               ->setParameter('type', $criteria['type']);
        }

        // Filtre par statut
        if (!empty($criteria['statut'])) {
            $qb->andWhere('e.statut = :statut')
               ->setParameter('statut', $criteria['statut']);
        }

        // Filtre par visibilité
        if (!empty($criteria['visibilite'])) {
            $qb->andWhere('e.visibilite = :visibilite')
               ->setParameter('visibilite', $criteria['visibilite']);
        }

        // Filtre par date de début
        if (!empty($criteria['date_debut'])) {
            $qb->andWhere('e.dateDebut >= :date_debut')
               ->setParameter('date_debut', $criteria['date_debut']);
        }

        // Tri
        $sortField = $criteria['sort'] ?? 'e.dateDebut';
        $sortDirection = $criteria['direction'] ?? 'DESC';
        
        $qb->orderBy($sortField, $sortDirection);

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre d'événements par statut (amélioration de votre méthode)
     */
    public function countByStatus(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.statut, COUNT(e.id) as count')
            ->groupBy('e.statut')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements à venir
     */
    public function findUpcoming(int $limit = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.dateDebut > :now')
            ->andWhere('e.statut = :statut')
            ->setParameter('now', new \DateTime())
            ->setParameter('statut', 'planifie')
            ->orderBy('e.dateDebut', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les événements par créateur
     */
    public function findByCreator(int $creatorId, array $criteria = []): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.createur = :creatorId')
            ->setParameter('creatorId', $creatorId);

        // Appliquer les filtres si présents
        if (!empty($criteria['statut'])) {
            $qb->andWhere('e.statut = :statut')
               ->setParameter('statut', $criteria['statut']);
        }

        // Tri par défaut par date récente
        $qb->orderBy('e.dateDebut', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Statistiques mensuelles
     */
    public function getMonthlyStats(int $year = null): array
    {
        if ($year === null) {
            $year = date('Y');
        }

        $startDate = new \DateTime("$year-01-01");
        $endDate = new \DateTime("$year-12-31");

        return $this->createQueryBuilder('e')
            ->select('MONTH(e.dateDebut) as month, COUNT(e.id) as count')
            ->where('e.dateDebut BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le nombre de participations pour une liste d'événements.
     *
     * @param int[] $eventIds
     * @return array<int, int> [eventId => participationCount]
     */
    public function getParticipationCountsByEventIds(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('e')
            ->select('e.id AS eventId, COUNT(p.id) AS participationCount')
            ->leftJoin('e.participations', 'p')
            ->where('e.id IN (:eventIds)')
            ->setParameter('eventIds', $eventIds)
            ->groupBy('e.id')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['eventId']] = (int) $row['participationCount'];
        }

        return $counts;
    }
}
