<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Entity\Enseignant;
use App\Entity\Administrateur;
use App\Repository\EtudiantRepository;
use App\Repository\EnseignantRepository;
use App\Repository\AdministrateurRepository;
use App\Service\AuthChecker;
use App\Service\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/utilisateurs')]
class UtilisateurController extends AbstractController
{
    #[Route('/', name: 'app_administrateur_utilisateurs', methods: ['GET', 'POST'])]
    public function index(
        EtudiantRepository $etudiantRepository,
        EnseignantRepository $enseignantRepository,
        AdministrateurRepository $administrateurRepository,
        AuthChecker $authChecker,
        Request $request
    ): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Check if user is admin
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }

        // Get search criteria from request
        $criteria = $request->query->all();

        // Limit the number of users per page for performance
        $limit = 50;
        $page = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * $limit;

        // Get users with pagination - more efficient
        $etudiants = $etudiantRepository->findBy([], ['dateCreation' => 'DESC'], $limit, $offset);
        $enseignants = $enseignantRepository->findBy([], ['dateCreation' => 'DESC'], $limit, $offset);
        $administrateurs = $administrateurRepository->findBy([], ['dateCreation' => 'DESC'], $limit, $offset);

        // Get total counts for statistics (more efficient than findAll())
        $totalEtudiants = $etudiantRepository->count([]);
        $totalEnseignants = $enseignantRepository->count([]);
        $totalAdministrateurs = $administrateurRepository->count([]);

        // Combine all users into one array with type information
        $allUsers = [];

        foreach ($etudiants as $etudiant) {
            $allUsers[] = [
                'id' => $etudiant->getId(),
                'type' => 'etudiant',  // Changed to lowercase
                'nom' => $etudiant->getNom(),
                'prenom' => $etudiant->getPrenom(),
                'email' => $etudiant->getEmail(),
                'matricule' => $etudiant->getMatricule(),
                'niveau' => $etudiant->getNiveauEtude(),
                'specialisation' => $etudiant->getSpecialisation(),
                'statut' => $etudiant->getStatut(),
                'date_creation' => $etudiant->getDateCreation(),
                'telephone' => $etudiant->getTelephone(),
                'entity' => $etudiant,
            ];
        }

        foreach ($enseignants as $enseignant) {
            $allUsers[] = [
                'id' => $enseignant->getId(),
                'type' => 'enseignant',  // Changed to lowercase
                'nom' => $enseignant->getNom(),
                'prenom' => $enseignant->getPrenom(),
                'email' => $enseignant->getEmail(),
                'matricule' => $enseignant->getMatriculeEnseignant(),
                'specialite' => $enseignant->getSpecialite(),
                'diplome' => $enseignant->getDiplome(),
                'contrat' => $enseignant->getTypeContrat(),
                'statut' => $enseignant->getStatut(),
                'date_creation' => $enseignant->getDateCreation(),
                'experience' => $enseignant->getAnneesExperience(),
                'entity' => $enseignant,
            ];
        }

        foreach ($administrateurs as $administrateur) {
            $allUsers[] = [
                'id' => $administrateur->getId(),
                'type' => 'administrateur',  // Changed to lowercase
                'nom' => $administrateur->getNom(),
                'prenom' => $administrateur->getPrenom(),
                'email' => $administrateur->getEmail(),
                'departement' => $administrateur->getDepartement(),
                'fonction' => $administrateur->getFonction(),
                'statut' => $administrateur->isActif() ? 'Actif' : 'Inactif',
                'date_creation' => $administrateur->getDateCreation(),
                'telephone' => $administrateur->getTelephone(),
                'entity' => $administrateur,
            ];
        }

        // Apply simple filtering - remove complex filtering for now
        if (!empty($criteria['type']) && $criteria['type'] !== 'all') {
            $allUsers = array_filter($allUsers, function($user) use ($criteria) {
                // Normalize type for comparison
                $userTypeDisplay = $user['type'] == 'etudiant' ? 'Étudiant' :
                    ($user['type'] == 'enseignant' ? 'Enseignant' : 'Administrateur');
                return $userTypeDisplay === $criteria['type'];
            });
        }

        // Apply simple search
        if (!empty($criteria['search'])) {
            $searchTerm = strtolower($criteria['search']);
            $allUsers = array_filter($allUsers, function($user) use ($searchTerm) {
                return str_contains(strtolower($user['nom']), $searchTerm) ||
                    str_contains(strtolower($user['prenom']), $searchTerm) ||
                    str_contains(strtolower($user['email']), $searchTerm) ||
                    (isset($user['matricule']) && str_contains(strtolower($user['matricule']), $searchTerm));
            });
        }

        // Apply simple status filter
        if (!empty($criteria['statut']) && $criteria['statut'] !== 'all') {
            $allUsers = array_filter($allUsers, function($user) use ($criteria) {
                return $user['statut'] === $criteria['statut'];
            });
        }

        // Apply sorting
        $sortBy = $request->query->get('sort', 'date_creation');
        $direction = $request->query->get('direction', 'desc');

        usort($allUsers, function($a, $b) use ($sortBy, $direction) {
            $valueA = $a[$sortBy] ?? '';
            $valueB = $b[$sortBy] ?? '';

            if ($valueA instanceof \DateTimeInterface && $valueB instanceof \DateTimeInterface) {
                $result = $valueA <=> $valueB;
            } elseif (is_numeric($valueA) && is_numeric($valueB)) {
                $result = $valueA <=> $valueB;
            } else {
                $result = strcmp((string)$valueA, (string)$valueB);
            }

            return $direction === 'desc' ? -$result : $result;
        });

        // Get filter options
        $filterOptions = [
            'types' => ['Étudiant', 'Enseignant', 'Administrateur'],
            'statuts' => ['actif', 'inactif', 'suspendu', 'diplome', 'conge', 'retraite', 'Actif', 'Inactif'],
        ];

        // Statistics
        $stats = [
            'total' => $totalEtudiants + $totalEnseignants + $totalAdministrateurs,
            'etudiants' => $totalEtudiants,
            'enseignants' => $totalEnseignants,
            'administrateurs' => $totalAdministrateurs,
            'page' => $page,
            'totalPages' => ceil(($totalEtudiants + $totalEnseignants + $totalAdministrateurs) / $limit),
            'limit' => $limit,
        ];

        return $this->render('admin/utilisateurs.html.twig', [
            'users' => $allUsers,
            'criteria' => $criteria,
            'stats' => $stats,
            'filterOptions' => $filterOptions,
            'sort' => $sortBy,
            'direction' => $direction,
        ]);
    }

    #[Route('/{type}/{id}', name: 'app_administrateur_utilisateur_show', methods: ['GET'])]
    public function show(
        string $type,
        int $id,
        EtudiantRepository $etudiantRepository,
        EnseignantRepository $enseignantRepository,
        AdministrateurRepository $administrateurRepository,
        AuthChecker $authChecker
    ): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Check if user is admin
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }

        // Normalize the type parameter to handle accents and case variations
        $normalizedType = $this->normalizeType($type);

        $user = null;

        switch ($normalizedType) {
            case 'etudiant':
                $user = $etudiantRepository->find($id);
                break;
            case 'enseignant':
                $user = $enseignantRepository->find($id);
                break;
            case 'administrateur':
                $user = $administrateurRepository->find($id);
                break;
            default:
                $this->addFlash('error', 'Type d\'utilisateur non valide.');
                return $this->redirectToRoute('app_administrateur_utilisateurs');
        }

        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('app_administrateur_utilisateurs');
        }

        return $this->render('admin/utilisateur_show.html.twig', [
            'user' => $user,
            'type' => $normalizedType,
        ]);
    }

    /**
     * Normalize user type to handle accents and case variations
     */
    private function normalizeType(string $type): string
    {
        // Convert to lowercase
        $type = strtolower($type);

        // Remove French accents
        $search = ['é', 'è', 'ê', 'ë', 'É', 'È', 'Ê', 'Ë'];
        $replace = ['e', 'e', 'e', 'e', 'e', 'e', 'e', 'e'];
        $type = str_replace($search, $replace, $type);

        // Remove any remaining special characters
        $type = preg_replace('/[^a-z]/', '', $type);

        return $type;
    }
}