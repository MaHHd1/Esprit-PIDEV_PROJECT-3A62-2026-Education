<?php

namespace App\Repository;

use App\Entity\Administrateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AdministrateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Administrateur::class);
    }

    public function findActiveAdmins(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('a.departement', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByDepartement(string $departement): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.departement = :departement')
            ->setParameter('departement', $departement)
            ->orderBy('a.fonction', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByDepartement(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.departement, COUNT(a.id) as count')
            ->groupBy('a.departement')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countOptimized(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}