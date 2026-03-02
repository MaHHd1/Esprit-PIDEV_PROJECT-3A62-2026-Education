<?php

namespace App\Repository;

use App\Entity\ContenuProgression;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ContenuProgressionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContenuProgression::class);
    }

    public function countCompletedByEtudiantAndCours(int $etudiantId, int $coursId): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.etudiant = :etudiantId')
            ->andWhere('p.cours = :coursId')
            ->andWhere('p.estTermine = :done')
            ->setParameter('etudiantId', $etudiantId)
            ->setParameter('coursId', $coursId)
            ->setParameter('done', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCompletedByCours(int $coursId): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.cours = :coursId')
            ->andWhere('p.estTermine = :done')
            ->setParameter('coursId', $coursId)
            ->setParameter('done', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumMinutesByEtudiantAndCours(int $etudiantId, int $coursId): int
    {
        $value = $this->createQueryBuilder('p')
            ->select('COALESCE(SUM(COALESCE(ct.duree, 1)), 0)')
            ->innerJoin('p.contenu', 'ct')
            ->andWhere('p.etudiant = :etudiantId')
            ->andWhere('p.cours = :coursId')
            ->andWhere('p.estTermine = :done')
            ->setParameter('etudiantId', $etudiantId)
            ->setParameter('coursId', $coursId)
            ->setParameter('done', true)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $value;
    }

    /**
     * @return array<int, array{
     *   etudiant_id:int,
     *   etudiant_nom:string,
     *   etudiant_prenom:string,
     *   etudiant_email:string,
     *   cours_id:int,
     *   cours_code:string,
     *   cours_titre:string,
     *   minutes:int
     * }>
     */
    public function findTimeSpentRowsByEnseignant(int $enseignantId): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $rows = $qb
            ->select(
                'e.id AS etudiant_id',
                'e.nom AS etudiant_nom',
                'e.prenom AS etudiant_prenom',
                'e.email AS etudiant_email',
                'c.id AS cours_id',
                'c.codeCours AS cours_code',
                'c.titre AS cours_titre',
                'COALESCE(t.secondes, 0) AS seconds_spent'
            )
            ->from(\App\Entity\Cours::class, 'c')
            ->innerJoin('c.enseignants', 'ens')
            ->innerJoin('c.etudiants', 'e')
            ->leftJoin(\App\Entity\CoursTempsPasse::class, 't', 'WITH', 't.cours = c AND t.etudiant = e')
            ->andWhere('ens.id = :enseignantId')
            ->setParameter('enseignantId', $enseignantId)
            ->groupBy('e.id, c.id')
            ->orderBy('seconds_spent', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static function (array $row): array {
            $seconds = (int) $row['seconds_spent'];
            $minutes = $seconds > 0 ? (int) ceil($seconds / 60) : 0;

            return [
                'etudiant_id' => (int) $row['etudiant_id'],
                'etudiant_nom' => (string) $row['etudiant_nom'],
                'etudiant_prenom' => (string) $row['etudiant_prenom'],
                'etudiant_email' => (string) $row['etudiant_email'],
                'cours_id' => (int) $row['cours_id'],
                'cours_code' => (string) $row['cours_code'],
                'cours_titre' => (string) $row['cours_titre'],
                'minutes' => $minutes,
            ];
        }, $rows);
    }

    /**
     * @return int[]
     */
    public function findCompletedContenuIds(int $etudiantId, int $coursId): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('IDENTITY(p.contenu) AS contenu_id')
            ->andWhere('p.etudiant = :etudiantId')
            ->andWhere('p.cours = :coursId')
            ->andWhere('p.estTermine = :done')
            ->setParameter('etudiantId', $etudiantId)
            ->setParameter('coursId', $coursId)
            ->setParameter('done', true)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row) => (int) $row['contenu_id'], $rows);
    }
}
