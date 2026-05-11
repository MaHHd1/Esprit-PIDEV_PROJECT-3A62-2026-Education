<?php

namespace App\Controller;

use App\Entity\Administrateur;
use App\Form\AdministrateurType;
use App\Repository\CoursRepository;
use App\Repository\ModuleRepository;
use App\Repository\EtudiantRepository;
use App\Repository\EnseignantRepository;
use App\Repository\AdministrateurRepository;
use App\Service\AuthChecker;
use App\Service\SearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/administrateur')]
class AdministrateurController extends AbstractController
{
    #[Route('/', name: 'app_administrateur_index', methods: ['GET', 'POST'])]
    public function index(
        AdministrateurRepository $administrateurRepository,
        AuthChecker $authChecker,
        Request $request,
        SearchService $searchService
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
        $criteria = [];
        if ($request->getMethod() === 'POST') {
            $criteria = $request->request->all();
        } else {
            $criteria = $request->query->all();
        }

        // Get filter options
        $filterOptions = $searchService->getDistinctValues('administrateur', $administrateurRepository);

        // Perform search if any criteria, otherwise get all with pagination
        if (!empty(array_filter($criteria))) {
            $administrateurs = $searchService->searchAdministrateurs($criteria, $administrateurRepository);
        } else {
            // Use pagination for performance
            $limit = 20;
            $page = max(1, (int) $request->query->get('page', 1));
            $offset = ($page - 1) * $limit;
            $administrateurs = $administrateurRepository->findBy([], ['dateCreation' => 'DESC'], $limit, $offset);
        }

        // Count statistics for dashboard
        $totalAdmins = $administrateurRepository->count([]);

        // Count active admins efficiently
        $activeAdmins = $administrateurRepository->count(['actif' => true]);
        $inactiveAdmins = $totalAdmins - $activeAdmins;

        // FIXED: Redirect to dashboard instead of trying to render non-existent template
        return $this->redirectToRoute('app_administrateur_dashboard');
    }

    #[Route('/dashboard', name: 'app_administrateur_dashboard', methods: ['GET'])]
    public function dashboard(
        EtudiantRepository $etudiantRepository,
        EnseignantRepository $enseignantRepository,
        AdministrateurRepository $administrateurRepository,
        AuthChecker $authChecker
    ): Response
    {
        // Authentication check - redirect if not logged in
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour accéder au dashboard.');
            return $this->redirectToRoute('app_login');
        }

        // Check if user is admin
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux administrateurs.');
            return $this->redirectToRoute('app_home');
        }

        // Get current user info
        $user = $authChecker->getCurrentUser();
        $userName = $user ? $user->getPrenom() . ' ' . $user->getNom() : null;
        $userEmail = $user ? $user->getEmail() : null;

        // Get statistics efficiently
        $totalEtudiants = $etudiantRepository->count([]);
        $totalEnseignants = $enseignantRepository->count([]);
        $totalAdministrateurs = $administrateurRepository->count([]);
        $activeAdmins = $administrateurRepository->count(['actif' => true]);

        // Get recent users (limited for performance)
        $recentEtudiants = $etudiantRepository->findBy([], ['dateCreation' => 'DESC'], 5);
        $recentEnseignants = $enseignantRepository->findBy([], ['dateCreation' => 'DESC'], 5);
        $recentAdmins = $administrateurRepository->findBy([], ['dateCreation' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'etudiants' => $totalEtudiants,
                'enseignants' => $totalEnseignants,
                'administrateurs' => $totalAdministrateurs,
                'active_admins' => $activeAdmins,
                'total_users' => $totalEtudiants + $totalEnseignants + $totalAdministrateurs,
            ],
            'recentEtudiants' => $recentEtudiants,
            'recentEnseignants' => $recentEnseignants,
            'recentAdmins' => $recentAdmins,
            'user_name' => $userName,
            'user_email' => $userEmail,
            'is_admin' => true,
            'is_enseignant' => false,
            'is_etudiant' => false,
        ]);
    }

    #[Route('/new', name: 'app_administrateur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AuthChecker $authChecker): Response
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

        $administrateur = new Administrateur();
        $form = $this->createForm(AdministrateurType::class, $administrateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash password
            $plainPassword = $form->get('motDePasse')->getData();
            $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
            $administrateur->setMotDePasse($hashedPassword);

            // Set creation date (already done in constructor, but ensure it's set)
            $administrateur->setDateCreation(new \DateTime());

            $entityManager->persist($administrateur);
            $entityManager->flush();

            $this->addFlash('success', 'Administrateur créé avec succès!');

            // If "save and add another" button was clicked, stay on the same page
            if ($request->request->has('save_and_new')) {
                return $this->redirectToRoute('app_administrateur_new');
            }

            // Otherwise redirect to the show page
            return $this->redirectToRoute('app_administrateur_utilisateur_show', [
                'type' => 'administrateur',
                'id' => $administrateur->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        // Render base_admin with the form content
        return $this->render('admin/administrateur_new.html.twig', [
            'administrateur' => $administrateur,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile', name: 'app_administrateur_profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        AuthChecker $authChecker,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Vérification authentification
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }

        $admin = $authChecker->getCurrentUser();

        if (!$admin instanceof Administrateur) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('app_home');
        }

        // Use the existing AdministrateurType form instead of creating a new one
        $form = $this->createForm(AdministrateurType::class, $admin, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update password only if provided
            if ($form->has('motDePasse') && $form->get('motDePasse')->getData()) {
                $plainPassword = $form->get('motDePasse')->getData();
                $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
                $admin->setMotDePasse($hashedPassword);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès !');
            return $this->redirectToRoute('app_administrateur_profile');
        }

        return $this->render('admin/profile.html.twig', [
            'admin' => $admin,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/statistiques', name: 'app_administrateur_statistiques', methods: ['GET'])]
    public function statistiques(
        EtudiantRepository $etudiantRepository,
        EnseignantRepository $enseignantRepository,
        AdministrateurRepository $administrateurRepository,
        CoursRepository $coursRepository,
        ModuleRepository $moduleRepository,
        AuthChecker $authChecker
    ): Response
    {
        // Vérification authentification
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }

        // Statistiques de base
        $totalEtudiants = $etudiantRepository->count([]);
        $totalEnseignants = $enseignantRepository->count([]);
        $totalAdministrateurs = $administrateurRepository->count([]);

        // Statistiques par statut (étudiants)
        $etudiantsParStatut = $etudiantRepository->createQueryBuilder('e')
            ->select('e.statut, COUNT(e.id) as count')
            ->groupBy('e.statut')
            ->getQuery()
            ->getResult();

        // Statistiques par statut (enseignants)
        $enseignantsParStatut = $enseignantRepository->createQueryBuilder('en')
            ->select('en.statut, COUNT(en.id) as count')
            ->groupBy('en.statut')
            ->getQuery()
            ->getResult();

        // Statistiques par type de contrat (enseignants)
        $enseignantsParContrat = $enseignantRepository->createQueryBuilder('en')
            ->select('en.typeContrat, COUNT(en.id) as count')
            ->where('en.typeContrat IS NOT NULL')
            ->groupBy('en.typeContrat')
            ->getQuery()
            ->getResult();

        // Statistiques par niveau d'étude (étudiants)
        $etudiantsParNiveau = $etudiantRepository->createQueryBuilder('e')
            ->select('e.niveauEtude, COUNT(e.id) as count')
            ->where('e.niveauEtude IS NOT NULL')
            ->groupBy('e.niveauEtude')
            ->getQuery()
            ->getResult();

        // Créations par mois (tous utilisateurs - derniers 6 mois)
        $sixMoisDate = (new \DateTime())->modify('-6 months');

        // Get all students created in last 6 months and group them manually in PHP
        $etudiantsCreations = $etudiantRepository->createQueryBuilder('e')
            ->where('e.dateCreation >= :date')
            ->setParameter('date', $sixMoisDate)
            ->getQuery()
            ->getResult();

        $creationsParMois = [];
        foreach ($etudiantsCreations as $etudiant) {
            $date = $etudiant->getDateCreation();
            $mois = $date->format('Y-m');

            if (!isset($creationsParMois[$mois])) {
                $creationsParMois[$mois] = 0;
            }
            $creationsParMois[$mois]++;
        }

        // Format for the template
        $formattedCreations = [];
        foreach ($creationsParMois as $mois => $count) {
            $formattedCreations[] = [
                'mois' => $mois,
                'count' => $count
            ];
        }

        // Sort by month
        usort($formattedCreations, function($a, $b) {
            return $a['mois'] <=> $b['mois'];
        });

        // Age moyen des étudiants
        $etudiants = $etudiantRepository->findAll();
        $totalAge = 0;
        $countWithAge = 0;

        foreach ($etudiants as $etudiant) {
            if ($etudiant->getDateNaissance()) {
                $age = $etudiant->getAge();
                $totalAge += $age;
                $countWithAge++;
            }
        }

        $ageMoyen = $countWithAge > 0 ? $totalAge / $countWithAge : 0;


        // Statistiques cours/modules
        $totalCours = $coursRepository->count([]);
        $totalModules = $moduleRepository->count([]);

        $coursParStatut = $coursRepository->createQueryBuilder('c')
            ->select('c.statut, COUNT(c.id) as count')
            ->groupBy('c.statut')
            ->getQuery()
            ->getResult();

        $modulesParStatut = $moduleRepository->createQueryBuilder('m')
            ->select('m.statut, COUNT(m.id) as count')
            ->groupBy('m.statut')
            ->getQuery()
            ->getResult();

        $coursParModule = $moduleRepository->createQueryBuilder('m')
            ->select('m.titreModule AS module, COUNT(c.id) AS count')
            ->leftJoin('m.cours', 'c')
            ->groupBy('m.id')
            ->orderBy('count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $inscriptionsParCours = $coursRepository->createQueryBuilder('c')
            ->select('c.codeCours AS code, c.titre AS titre, COUNT(e.id) AS count')
            ->leftJoin('c.etudiants', 'e')
            ->groupBy('c.id')
            ->orderBy('count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
        // Préparer les données pour les graphiques
        $stats = [
            'totaux' => [
                'etudiants' => $totalEtudiants,
                'enseignants' => $totalEnseignants,
                'administrateurs' => $totalAdministrateurs,
                'total' => $totalEtudiants + $totalEnseignants + $totalAdministrateurs,
            ],
            'totaux_cours_modules' => [
                'cours' => $totalCours,
                'modules' => $totalModules,
            ],
            'par_statut' => [
                'etudiants' => $etudiantsParStatut,
                'enseignants' => $enseignantsParStatut,
                'cours' => $coursParStatut,
                'modules' => $modulesParStatut,
            ],
            'par_contrat' => $enseignantsParContrat,
            'par_niveau' => $etudiantsParNiveau,
            'creations_par_mois' => $formattedCreations,
            'age_moyen' => round($ageMoyen, 1),
            'cours_par_module' => $coursParModule,
            'inscriptions_par_cours' => $inscriptionsParCours,
        ];

        return $this->render('admin/statistiques.html.twig', [
            'stats' => $stats,
        ]);
    }

    // ========== SPECIFIC ROUTES MUST BE ABOVE GENERIC {id} ROUTES ==========
    #[Route('/profile/changer-motdepasse', name: 'app_administrateur_changer_motdepasse', methods: ['POST'])]
    public function changerMotDePasse(
        Request $request,
        AuthChecker $authChecker,
        EntityManagerInterface $entityManager
    ): Response
    {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $admin = $authChecker->getCurrentUser();
        $ancienMotDePasse = $request->request->get('ancien_motdepasse');
        $nouveauMotDePasse = $request->request->get('nouveau_motdepasse');
        $confirmation = $request->request->get('confirmation_motdepasse');

        // Validation
        if (!password_verify($ancienMotDePasse, $admin->getMotDePasse())) {
            $this->addFlash('error', 'Ancien mot de passe incorrect.');
        } elseif ($nouveauMotDePasse !== $confirmation) {
            $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
        } elseif (strlen($nouveauMotDePasse) < 8) {
            $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
        } else {
            $hashedPassword = password_hash($nouveauMotDePasse, PASSWORD_BCRYPT);
            $admin->setMotDePasse($hashedPassword);
            $entityManager->flush();
            $this->addFlash('success', 'Mot de passe changé avec succès !');
        }

        return $this->redirectToRoute('app_administrateur_profile');
    }

    // ========== GENERIC {id} ROUTES ==========
    #[Route('/{id}', name: 'app_administrateur_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        int $id,
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

        $administrateur = $administrateurRepository->find($id);

        if (!$administrateur) {
            $this->addFlash('error', 'Administrateur non trouvé.');
            return $this->redirectToRoute('app_administrateur_utilisateurs');
        }

        // Redirect to the combined user show page
        return $this->redirectToRoute('app_administrateur_utilisateur_show', [
            'type' => 'administrateur',
            'id' => $administrateur->getId(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_administrateur_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        int $id,
        AdministrateurRepository $administrateurRepository,
        EntityManagerInterface $entityManager,
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

        $administrateur = $administrateurRepository->find($id);

        if (!$administrateur) {
            $this->addFlash('error', 'Administrateur non trouvé.');
            return $this->redirectToRoute('app_administrateur_utilisateurs');
        }

        $form = $this->createForm(AdministrateurType::class, $administrateur, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update password only if provided
            if ($form->has('motDePasse') && $form->get('motDePasse')->getData()) {
                $plainPassword = $form->get('motDePasse')->getData();
                $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
                $administrateur->setMotDePasse($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Administrateur modifié avec succès!');

            // If "save and continue" button was clicked, stay on the same page
            if ($request->request->has('save_and_continue')) {
                return $this->redirectToRoute('app_administrateur_edit', ['id' => $administrateur->getId()]);
            }

            // Otherwise redirect to the show page
            return $this->redirectToRoute('app_administrateur_utilisateur_show', [
                'type' => 'administrateur',
                'id' => $administrateur->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        // Render base_admin with the form content
        return $this->render('admin/administrateur_edit.html.twig', [
            'administrateur' => $administrateur,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_administrateur_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        int $id,
        AdministrateurRepository $administrateurRepository,
        EntityManagerInterface $entityManager,
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

        $administrateur = $administrateurRepository->find($id);

        if (!$administrateur) {
            $this->addFlash('error', 'Administrateur non trouvé.');
            return $this->redirectToRoute('app_administrateur_utilisateurs');
        }

        if ($this->isCsrfTokenValid('delete'.$administrateur->getId(), $request->request->get('_token'))) {
            $entityManager->remove($administrateur);
            $entityManager->flush();

            $this->addFlash('success', 'Administrateur supprimé avec succès!');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_administrateur_utilisateurs', [], Response::HTTP_SEE_OTHER);
    }
}
