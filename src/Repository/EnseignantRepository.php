<?php

namespace App\Repository;

use App\Entity\Enseignant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EnseignantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enseignant::class);
    }

    public function findByMatriculeEnseignant(string $matricule): ?Enseignant
    {
        return $this->findOneBy(['matriculeEnseignant' => $matricule]);
    }

    public function findActiveTeachers(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.statut = :statut')
            ->setParameter('statut', 'actif')
            ->orderBy('e.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findBySpecialite(string $specialite): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.specialite = :specialite')
            ->setParameter('specialite', $specialite)
            ->orderBy('e.anneesExperience', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findVacataires(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.typeContrat = :type')
            ->setParameter('type', 'Vacataire')
            ->orderBy('e.tauxHoraire', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getAverageExperience(): float
    {
        $result = $this->createQueryBuilder('e')
            ->select('AVG(e.anneesExperience) as avg_exp')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0.0;
    }

    public function findUniqueSpecialites(): array
    {
        $results = $this->createQueryBuilder('e')
            ->select('DISTINCT e.specialite')
            ->orderBy('e.specialite', 'ASC')
            ->getQuery()
            ->getResult();

        $specs = [];
        foreach ($results as $result) {
            if (!empty($result['specialite'])) {
                $specs[] = $result['specialite'];
            }
        }
        return $specs;
    }

    public function countOptimized(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}