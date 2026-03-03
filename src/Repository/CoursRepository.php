<?php

namespace App\Repository;

use App\Entity\Cours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cours::class);
    }

    public function findByCode(string $code): ?Cours
    {
        return $this->findOneBy(['codeCours' => $code]);
    }

    /**
     * @return Cours[]
     */
    public function findByEnseignantId(int $enseignantId): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.enseignants', 'e')
            ->andWhere('e.id = :enseignantId')
            ->setParameter('enseignantId', $enseignantId)
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array{q?:string, statut?:string, sort?:string} $filters
     * @return Cours[]
     */
    public function findByModuleWithFilters(int $moduleId, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.module = :moduleId')
            ->setParameter('moduleId', $moduleId);

        $this->applyCommonFilters($qb, $filters);
        $this->applySorting($qb, $filters['sort'] ?? null, 'c.dateCreation', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array{q?:string, statut?:string, sort?:string} $filters
     * @return Cours[]
     */
    public function findByEnseignantIdWithFilters(int $enseignantId, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.enseignants', 'e')
            ->andWhere('e.id = :enseignantId')
            ->setParameter('enseignantId', $enseignantId);

        $this->applyCommonFilters($qb, $filters);
        $this->applySorting($qb, $filters['sort'] ?? null, 'c.dateCreation', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function isAssignedToEnseignant(int $coursId, int $enseignantId): bool
    {
        $count = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->innerJoin('c.enseignants', 'e')
            ->andWhere('c.id = :coursId')
            ->andWhere('e.id = :enseignantId')
            ->setParameter('coursId', $coursId)
            ->setParameter('enseignantId', $enseignantId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    /**
     * @return Cours[]
     */
    public function findEnrolledByEtudiantId(int $etudiantId): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.etudiants', 'e')
            ->andWhere('e.id = :etudiantId')
            ->setParameter('etudiantId', $etudiantId)
            ->orderBy('c.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array{q?:string, sort?:string, module_id?:string} $filters
     * @return Cours[]
     */
    public function findEnrolledByEtudiantIdWithFilters(int $etudiantId, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.etudiants', 'e')
            ->andWhere('e.id = :etudiantId')
            ->setParameter('etudiantId', $etudiantId);

        $this->applyCommonFilters($qb, $filters);
        $this->applySorting($qb, $filters['sort'] ?? null, 'c.titre', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Cours[]
     */
    public function findAvailableForEtudiantId(int $etudiantId): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.etudiants', 'e', 'WITH', 'e.id = :etudiantId')
            ->andWhere('e.id IS NULL')
            ->andWhere('c.statut = :statut')
            ->setParameter('etudiantId', $etudiantId)
            ->setParameter('statut', 'ouvert')
            ->orderBy('c.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array{q?:string, sort?:string, module_id?:string} $filters
     * @return Cours[]
     */
    public function findAvailableForEtudiantIdWithFilters(int $etudiantId, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.etudiants', 'e', 'WITH', 'e.id = :etudiantId')
            ->andWhere('e.id IS NULL')
            ->andWhere('c.statut = :statut')
            ->setParameter('etudiantId', $etudiantId)
            ->setParameter('statut', 'ouvert');

        $this->applyCommonFilters($qb, $filters);
        $this->applySorting($qb, $filters['sort'] ?? null, 'c.titre', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array{q?:string, statut?:string, module_id?:string} $filters
     */
    private function applyCommonFilters(\Doctrine\ORM\QueryBuilder $qb, array $filters): void
    {
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $qb->andWhere('c.titre LIKE :search OR c.codeCours LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%'.$q.'%');
        }

        $statut = (string) ($filters['statut'] ?? '');
        if ($statut !== '' && in_array($statut, ['brouillon', 'ouvert', 'ferme', 'archive'], true)) {
            $qb->andWhere('c.statut = :statutFilter')
                ->setParameter('statutFilter', $statut);
        }

        $moduleId = (int) ($filters['module_id'] ?? 0);
        if ($moduleId > 0) {
            $qb->andWhere('c.module = :moduleFilter')
                ->setParameter('moduleFilter', $moduleId);
        }
    }

    private function applySorting(\Doctrine\ORM\QueryBuilder $qb, ?string $sort, string $defaultField, string $defaultDirection): void
    {
        if ($sort === 'title_desc') {
            $qb->orderBy('c.titre', 'DESC');
            return;
        }

        if ($sort === 'recent') {
            $qb->orderBy('c.dateCreation', 'DESC');
            return;
        }

        $qb->orderBy($defaultField, $defaultDirection);
    }
}
