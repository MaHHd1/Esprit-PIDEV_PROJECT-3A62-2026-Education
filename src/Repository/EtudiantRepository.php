<?php

namespace App\Repository;

use App\Entity\Etudiant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EtudiantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Etudiant::class);
    }

    public function findByMatricule(string $matricule): ?Etudiant
    {
        return $this->findOneBy(['matricule' => $matricule]);
    }

    public function findActiveStudents(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.statut = :statut')
            ->setParameter('statut', 'actif')
            ->orderBy('e.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByNiveauAndSpecialisation(string $niveau, string $specialisation): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.niveauEtude = :niveau')
            ->andWhere('e.specialisation = :specialisation')
            ->setParameter('niveau', $niveau)
            ->setParameter('specialisation', $specialisation)
            ->orderBy('e.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatut(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.statut, COUNT(e.id) as count')
            ->groupBy('e.statut')
            ->getQuery()
            ->getResult();
    }

    public function countOptimized(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param array{q?:string, cours_id?:int} $filters
     * @return Etudiant[]
     */
    public function findByEnseignantIdWithFilters(int $enseignantId, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('e')
            ->distinct()
            ->innerJoin('e.coursInscrits', 'c')
            ->innerJoin('c.enseignants', 'ens')
            ->andWhere('ens.id = :enseignantId')
            ->setParameter('enseignantId', $enseignantId);

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $qb->andWhere('e.nom LIKE :search OR e.prenom LIKE :search OR e.email LIKE :search OR e.matricule LIKE :search')
                ->setParameter('search', '%'.$q.'%');
        }

        $coursId = (int) ($filters['cours_id'] ?? 0);
        if ($coursId > 0) {
            $qb->andWhere('c.id = :coursId')
                ->setParameter('coursId', $coursId);
        }

        return $qb->orderBy('e.nom', 'ASC')
            ->addOrderBy('e.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
