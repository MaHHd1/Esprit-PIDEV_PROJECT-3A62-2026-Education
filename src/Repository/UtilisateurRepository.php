<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function findByEmail(string $email): ?Utilisateur
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function searchByName(string $search): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.nom LIKE :search OR u.prenom LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByType(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.typeUtilisateur as type, COUNT(u.id) as count')
            ->groupBy('u.typeUtilisateur')
            ->getQuery()
            ->getResult();
    }
    public function findValidResetToken(string $token): ?Utilisateur
    {
        return $this->createQueryBuilder('u')
            ->where('u.resetToken = :token')
            ->andWhere('u.resetTokenExpiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }
}