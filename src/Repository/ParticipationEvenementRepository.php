<?php

namespace App\Repository;

use App\Entity\ParticipationEvenement;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParticipationEvenement>
 */
class ParticipationEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticipationEvenement::class);
    }

    public function findRecent(int $limit = 5): array
{
    return $this->createQueryBuilder('p')
        ->orderBy('p.dateInscription', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}

    /**
     * @return ParticipationEvenement[]
     */
    public function findUpcomingForUser(
        Utilisateur $user,
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): array {
        return $this->createQueryBuilder('p')
            ->addSelect('e')
            ->innerJoin('p.evenement', 'e')
            ->andWhere('p.utilisateur = :user')
            ->andWhere('e.dateDebut > :from')
            ->andWhere('e.dateDebut <= :to')
            ->orderBy('e.dateDebut', 'ASC')
            ->setParameter('user', $user)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }


    //    /**
    //     * @return ParticipationEvenement[] Returns an array of ParticipationEvenement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ParticipationEvenement
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
