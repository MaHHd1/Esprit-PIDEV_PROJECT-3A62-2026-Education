<?php

namespace App\Repository;

use App\Entity\CoursVue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CoursVueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CoursVue::class);
    }

    /**
     * @return array<int, array{cours_id:int,cours_code:string,cours_titre:string,views:int}>
     */
    public function findTopViewedCoursesByEnseignant(int $enseignantId, int $limit = 5): array
    {
        $rows = $this->createQueryBuilder('cv')
            ->select(
                'c.id AS cours_id',
                'c.codeCours AS cours_code',
                'c.titre AS cours_titre',
                'COUNT(cv.id) AS views'
            )
            ->innerJoin('cv.cours', 'c')
            ->innerJoin('c.enseignants', 'ens')
            ->andWhere('ens.id = :enseignantId')
            ->setParameter('enseignantId', $enseignantId)
            ->groupBy('c.id')
            ->orderBy('views', 'DESC')
            ->addOrderBy('c.titre', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'cours_id' => (int) $row['cours_id'],
            'cours_code' => (string) $row['cours_code'],
            'cours_titre' => (string) $row['cours_titre'],
            'views' => (int) $row['views'],
        ], $rows);
    }

    /**
     * @return array<int, array{
     *   cours_id:int,
     *   cours_code:string,
     *   cours_titre:string,
     *   views:int
     * }>
     */
    public function findViewedCourseStatsByEnseignant(int $enseignantId): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'c.id AS cours_id',
                'c.codeCours AS cours_code',
                'c.titre AS cours_titre',
                'COUNT(cv.id) AS views'
            )
            ->from(\App\Entity\Cours::class, 'c')
            ->innerJoin('c.enseignants', 'ens')
            ->leftJoin(\App\Entity\CoursVue::class, 'cv', 'WITH', 'cv.cours = c')
            ->andWhere('ens.id = :enseignantId')
            ->setParameter('enseignantId', $enseignantId)
            ->groupBy('c.id')
            ->orderBy('views', 'DESC')
            ->addOrderBy('c.titre', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'cours_id' => (int) $row['cours_id'],
            'cours_code' => (string) $row['cours_code'],
            'cours_titre' => (string) $row['cours_titre'],
            'views' => (int) $row['views'],
        ], $rows);
    }
}
