<?php

namespace App\Repository;

use App\Entity\Module;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Module::class);
    }

    /**
     * @param array{q?:string, statut?:string, sort?:string} $filters
     * @return Module[]
     */
    public function findWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('m');

        $this->applyFilters($qb, $filters);
        $this->applySorting($qb, (string) ($filters['sort'] ?? 'ordre_asc'));

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array{q?:string, statut?:string} $filters
     */
    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $qb->andWhere('m.titreModule LIKE :search OR m.description LIKE :search OR m.objectifsApprentissage LIKE :search')
                ->setParameter('search', '%'.$q.'%');
        }

        $statut = (string) ($filters['statut'] ?? '');
        if ($statut !== '' && in_array($statut, ['brouillon', 'publie', 'archive'], true)) {
            $qb->andWhere('m.statut = :statut')
                ->setParameter('statut', $statut);
        }
    }

    private function applySorting(QueryBuilder $qb, string $sort): void
    {
        if ($sort === 'titre_asc') {
            $qb->orderBy('m.titreModule', 'ASC');
            return;
        }

        if ($sort === 'titre_desc') {
            $qb->orderBy('m.titreModule', 'DESC');
            return;
        }

        if ($sort === 'ordre_desc') {
            $qb->orderBy('m.ordreAffichage', 'DESC');
            return;
        }

        $qb->orderBy('m.ordreAffichage', 'ASC');
    }
}
