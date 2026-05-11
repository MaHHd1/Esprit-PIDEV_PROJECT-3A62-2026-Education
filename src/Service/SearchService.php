<?php

namespace App\Service;

use App\Repository\EtudiantRepository;
use App\Repository\EnseignantRepository;
use App\Repository\AdministrateurRepository;

class SearchService
{
    public function searchEtudiants(array $criteria, EtudiantRepository $repository): array
    {
        $queryBuilder = $repository->createQueryBuilder('e');

        // Keyword search
        if (!empty($criteria['keyword'])) {
            $queryBuilder
                ->andWhere('e.nom LIKE :keyword OR e.prenom LIKE :keyword OR e.email LIKE :keyword OR e.matricule LIKE :keyword')
                ->setParameter('keyword', '%' . $criteria['keyword'] . '%');
        }

        // Status filter
        if (!empty($criteria['statut'])) {
            $queryBuilder
                ->andWhere('e.statut = :statut')
                ->setParameter('statut', $criteria['statut']);
        }

        // Niveau filter
        if (!empty($criteria['niveau'])) {
            $queryBuilder
                ->andWhere('e.niveauEtude = :niveau')
                ->setParameter('niveau', $criteria['niveau']);
        }

        // Specialisation filter
        if (!empty($criteria['specialisation'])) {
            $queryBuilder
                ->andWhere('e.specialisation = :specialisation')
                ->setParameter('specialisation', $criteria['specialisation']);
        }

        // Matricule filter
        if (!empty($criteria['matricule'])) {
            $queryBuilder
                ->andWhere('e.matricule LIKE :matricule')
                ->setParameter('matricule', '%' . $criteria['matricule'] . '%');
        }

        // Date range filter
        if (!empty($criteria['dateFrom'])) {
            $queryBuilder
                ->andWhere('e.dateInscription >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($criteria['dateFrom']));
        }

        if (!empty($criteria['dateTo'])) {
            $queryBuilder
                ->andWhere('e.dateInscription <= :dateTo')
                ->setParameter('dateTo', new \DateTime($criteria['dateTo'] . ' 23:59:59'));
        }

        // Sorting - Default to nom ASC
        $sortBy = $criteria['sortBy'] ?? 'nom';
        $sortOrder = $criteria['sortOrder'] ?? 'ASC';

        // Map sort options to actual entity fields
        $sortMapping = [
            'nom' => 'e.nom',
            'prenom' => 'e.prenom',
            'email' => 'e.email',
            'matricule' => 'e.matricule',
            'niveau' => 'e.niveauEtude',
            'specialisation' => 'e.specialisation',
            'dateInscription' => 'e.dateInscription',
            'dateNaissance' => 'e.dateNaissance',
            'telephone' => 'e.telephone',
        ];

        if (isset($sortMapping[$sortBy])) {
            $queryBuilder->orderBy($sortMapping[$sortBy], $sortOrder);
        } else {
            $queryBuilder->orderBy('e.nom', $sortOrder);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function searchEnseignants(array $criteria, EnseignantRepository $repository): array
    {
        $queryBuilder = $repository->createQueryBuilder('e');

        // Keyword search
        if (!empty($criteria['keyword'])) {
            $queryBuilder
                ->andWhere('e.nom LIKE :keyword OR e.prenom LIKE :keyword OR e.email LIKE :keyword OR e.matriculeEnseignant LIKE :keyword')
                ->setParameter('keyword', '%' . $criteria['keyword'] . '%');
        }

        // Status filter
        if (!empty($criteria['statut'])) {
            $queryBuilder
                ->andWhere('e.statut = :statut')
                ->setParameter('statut', $criteria['statut']);
        }

        // Specialite filter
        if (!empty($criteria['specialite'])) {
            $queryBuilder
                ->andWhere('e.specialite = :specialite')
                ->setParameter('specialite', $criteria['specialite']);
        }

        // Diplome filter
        if (!empty($criteria['diplome'])) {
            $queryBuilder
                ->andWhere('e.diplome = :diplome')
                ->setParameter('diplome', $criteria['diplome']);
        }

        // Type de contrat filter
        if (!empty($criteria['typeContrat'])) {
            $queryBuilder
                ->andWhere('e.typeContrat = :typeContrat')
                ->setParameter('typeContrat', $criteria['typeContrat']);
        }

        // Matricule filter
        if (!empty($criteria['matricule'])) {
            $queryBuilder
                ->andWhere('e.matriculeEnseignant LIKE :matricule')
                ->setParameter('matricule', '%' . $criteria['matricule'] . '%');
        }

        // Experience range
        if (!empty($criteria['minExperience'])) {
            $queryBuilder
                ->andWhere('e.anneesExperience >= :minExp')
                ->setParameter('minExp', $criteria['minExperience']);
        }

        if (!empty($criteria['maxExperience'])) {
            $queryBuilder
                ->andWhere('e.anneesExperience <= :maxExp')
                ->setParameter('maxExp', $criteria['maxExperience']);
        }

        // Sorting - Default to nom ASC
        $sortBy = $criteria['sortBy'] ?? 'nom';
        $sortOrder = $criteria['sortOrder'] ?? 'ASC';

        // Map sort options to actual entity fields
        $sortMapping = [
            'nom' => 'e.nom',
            'prenom' => 'e.prenom',
            'email' => 'e.email',
            'matricule' => 'e.matriculeEnseignant',
            'specialite' => 'e.specialite',
            'diplome' => 'e.diplome',
            'experience' => 'e.anneesExperience',
            'typeContrat' => 'e.typeContrat',
            'dateCreation' => 'e.dateCreation',
        ];

        if (isset($sortMapping[$sortBy])) {
            $queryBuilder->orderBy($sortMapping[$sortBy], $sortOrder);
        } else {
            $queryBuilder->orderBy('e.nom', $sortOrder);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function searchAdministrateurs(array $criteria, AdministrateurRepository $repository): array
    {
        $queryBuilder = $repository->createQueryBuilder('a');

        // Keyword search
        if (!empty($criteria['keyword'])) {
            $queryBuilder
                ->andWhere('a.nom LIKE :keyword OR a.prenom LIKE :keyword OR a.email LIKE :keyword OR a.fonction LIKE :keyword')
                ->setParameter('keyword', '%' . $criteria['keyword'] . '%');
        }

        // Status filter (actif)
        if (isset($criteria['actif'])) {
            $queryBuilder
                ->andWhere('a.actif = :actif')
                ->setParameter('actif', $criteria['actif']);
        }

        // Departement filter
        if (!empty($criteria['departement'])) {
            $queryBuilder
                ->andWhere('a.departement = :departement')
                ->setParameter('departement', $criteria['departement']);
        }

        // Fonction filter
        if (!empty($criteria['fonction'])) {
            $queryBuilder
                ->andWhere('a.fonction LIKE :fonction')
                ->setParameter('fonction', '%' . $criteria['fonction'] . '%');
        }

        // Sorting - Default to nom ASC
        $sortBy = $criteria['sortBy'] ?? 'nom';
        $sortOrder = $criteria['sortOrder'] ?? 'ASC';

        // Map sort options to actual entity fields
        $sortMapping = [
            'nom' => 'a.nom',
            'prenom' => 'a.prenom',
            'email' => 'a.email',
            'departement' => 'a.departement',
            'fonction' => 'a.fonction',
            'telephone' => 'a.telephone',
            'dateNomination' => 'a.dateNomination',
            'dateCreation' => 'a.dateCreation',
        ];

        if (isset($sortMapping[$sortBy])) {
            $queryBuilder->orderBy($sortMapping[$sortBy], $sortOrder);
        } else {
            $queryBuilder->orderBy('a.nom', $sortOrder);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getDistinctValues(string $entity, $repository): array
    {
        switch ($entity) {
            case 'etudiant':
                return [
                    'niveaux' => $repository->createQueryBuilder('e')
                        ->select('DISTINCT e.niveauEtude')
                        ->orderBy('e.niveauEtude', 'ASC')
                        ->getQuery()
                        ->getSingleColumnResult(),
                    'specialisations' => $repository->createQueryBuilder('e')
                        ->select('DISTINCT e.specialisation')
                        ->orderBy('e.specialisation', 'ASC')
                        ->getQuery()
                        ->getSingleColumnResult(),
                ];

            case 'enseignant':
                return [
                    'specialites' => $repository->createQueryBuilder('e')
                        ->select('DISTINCT e.specialite')
                        ->orderBy('e.specialite', 'ASC')
                        ->getQuery()
                        ->getSingleColumnResult(),
                    'diplomes' => $repository->createQueryBuilder('e')
                        ->select('DISTINCT e.diplome')
                        ->orderBy('e.diplome', 'ASC')
                        ->getQuery()
                        ->getSingleColumnResult(),
                    'contrats' => $repository->createQueryBuilder('e')
                        ->select('DISTINCT e.typeContrat')
                        ->orderBy('e.typeContrat', 'ASC')
                        ->getQuery()
                        ->getSingleColumnResult(),
                ];

            case 'administrateur':
                return [
                    'departements' => $repository->createQueryBuilder('a')
                        ->select('DISTINCT a.departement')
                        ->orderBy('a.departement', 'ASC')
                        ->getQuery()
                        ->getSingleColumnResult(),
                ];
        }

        return [];
    }
}