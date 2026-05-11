<?php

namespace App\Controller;

use App\Entity\Enseignant;
use App\Form\EnseignantType;
use App\Form\ChangePasswordType;
use App\Repository\CoursTempsPasseRepository;
use App\Repository\CoursRepository;
use App\Repository\ContenuProgressionRepository;
use App\Repository\CoursVueRepository;
use App\Repository\EnseignantRepository;
use App\Repository\EtudiantRepository;
use App\Service\AuthChecker;
use App\Service\SearchService;
use App\Service\SimplePaginator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/enseignant')]
class EnseignantController extends AbstractController
{
    #[Route('/dashboard', name: 'app_enseignant_dashboard', methods: ['GET'])]
    public function dashboard(
        AuthChecker $authChecker,
        ContenuProgressionRepository $progressionRepository
    ): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Check if user is a teacher
        if (!$authChecker->isEnseignant()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $authChecker->getCurrentUser();
        $timeSpentRows = [];
        $totalStudents = 0;
        $activeStudents = 0;

        if ($enseignant instanceof Enseignant) {
            $timeSpentRows = $progressionRepository->findTimeSpentRowsByEnseignant((int) $enseignant->getId());
            $studentIds = array_values(array_unique(array_map(static fn (array $r): int => $r['etudiant_id'], $timeSpentRows)));
            $totalStudents = count($studentIds);
            $activeStudents = count(array_filter($timeSpentRows, static fn (array $r): bool => $r['minutes'] > 0));
        }

        return $this->render('enseignant/dashboard.html.twig', [
            'enseignant' => $enseignant,
            'coursesCount' => $enseignant instanceof Enseignant ? $enseignant->getCours()->count() : 0,
            'time_spent_rows' => $timeSpentRows,
            'total_students' => $totalStudents,
            'active_students' => $activeStudents,
        ]);
    }

    #[Route('/statistiques', name: 'app_enseignant_statistiques', methods: ['GET'])]
    public function statistiques(
        AuthChecker $authChecker,
        CoursVueRepository $coursVueRepository,
        CoursTempsPasseRepository $coursTempsPasseRepository
    ): Response {
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$authChecker->isEnseignant()) {
            $this->addFlash('error', 'Acces non autorise.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $authChecker->getCurrentUser();
        if (!$enseignant instanceof Enseignant) {
            $this->addFlash('error', 'Utilisateur invalide.');
            return $this->redirectToRoute('app_home');
        }

        $viewsByCourse = $coursVueRepository->findViewedCourseStatsByEnseignant((int) $enseignant->getId());
        $avgTimeByCourse = $coursTempsPasseRepository->findAverageMinutesByCourseForEnseignant((int) $enseignant->getId());

        return $this->render('enseignant/statistiques.html.twig', [
            'enseignant' => $enseignant,
            'views_by_course' => $viewsByCourse,
            'avg_time_by_course' => $avgTimeByCourse,
        ]);
    }

    #[Route('/', name: 'app_enseignant_index', methods: ['GET', 'POST'])]
    public function index(
        EnseignantRepository $enseignantRepository,
        AuthChecker $authChecker,
        Request $request,
        SearchService $searchService
    ): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // If user is a teacher, redirect to dashboard
        if ($authChecker->isEnseignant()) {
            return $this->redirectToRoute('app_enseignant_dashboard');
        }

        // Only admins can see the teacher list
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux administrateurs.');
            return $this->redirectToRoute('app_home');
        }

        // Redirect to the combined users list
        return $this->redirectToRoute('app_administrateur_utilisateurs');
    }

    // ========== SPECIFIC ROUTES MUST BE ABOVE GENERIC {id} ROUTES ==========

    #[Route('/new', name: 'app_enseignant_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AuthChecker $authChecker): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Only admins can create teachers
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux administrateurs.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = new Enseignant();
        $form = $this->createForm(EnseignantType::class, $enseignant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash password
            $plainPassword = $form->get('motDePasse')->getData();
            $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
            $enseignant->setMotDePasse($hashedPassword);

            $entityManager->persist($enseignant);
            $entityManager->flush();

            $this->addFlash('success', 'Enseignant créé avec succès!');

            // If "save and add another" button was clicked, stay on the same page
            if ($request->request->has('save_and_new')) {
                return $this->redirectToRoute('app_enseignant_new');
            }

            // Otherwise redirect to the show page
            return $this->redirectToRoute('app_administrateur_utilisateur_show', [
                'type' => 'enseignant',
                'id' => $enseignant->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/enseignant_new.html.twig', [
            'enseignant' => $enseignant,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/liste-etudiants', name: 'app_enseignant_liste_etudiants', methods: ['GET'])]
    public function listeEtudiants(
        Request $request,
        EtudiantRepository $etudiantRepository,
        CoursRepository $coursRepository,
        AuthChecker $authChecker,
        SimplePaginator $paginator
    ): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Check if user is a teacher
        if (!$authChecker->isEnseignant()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $authChecker->getCurrentUser();
        if (!$enseignant instanceof Enseignant) {
            $this->addFlash('error', 'Utilisateur invalide.');
            return $this->redirectToRoute('app_home');
        }

        $filters = [
            'q' => trim((string) $request->query->get('q', '')),
            'cours_id' => (int) $request->query->get('cours_id', 0),
        ];

        $etudiants = $etudiantRepository->findByEnseignantIdWithFilters((int) $enseignant->getId(), $filters);
        $pagination = $paginator->paginateArray($etudiants, (int) $request->query->get('page', 1), 15);
        $etudiants = $pagination['items'];
        $cours = $coursRepository->findByEnseignantId((int) $enseignant->getId());

        $stats = [
            'actif' => 0,
            'diplome' => 0,
            'inactif' => 0,
        ];

        foreach ($etudiants as $etudiant) {
            $statut = (string) $etudiant->getStatut();
            if (isset($stats[$statut])) {
                $stats[$statut]++;
            }
        }

        return $this->render('enseignant/liste_etudiants.html.twig', [
            'enseignant' => $enseignant,
            'etudiants' => $etudiants,
            'cours' => $cours,
            'filters' => $filters,
            'stats' => $stats,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/edit-profile', name: 'app_enseignant_edit_profile', methods: ['GET', 'POST'])]
    public function editProfile(
        Request $request,
        AuthChecker $authChecker,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Check if user is a teacher
        if (!$authChecker->isEnseignant()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $authChecker->getCurrentUser();

        // Create form using EnseignantType with is_edit option
        $form = $this->createForm(EnseignantType::class, $enseignant, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Update password only if provided
                if ($form->has('motDePasse') && $form->get('motDePasse')->getData()) {
                    $plainPassword = $form->get('motDePasse')->getData();
                    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
                    $enseignant->setMotDePasse($hashedPassword);
                }

                $entityManager->flush();
                $this->addFlash('success', 'Profil mis à jour avec succès !');

                return $this->redirectToRoute('app_enseignant_edit_profile');
            } else {
                // Form has validation errors
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
            }
        }

        return $this->render('enseignant/edit_profile.html.twig', [
            'enseignant' => $enseignant,
            'edit_form' => $form->createView(), // Pass as 'edit_form'
        ]);
    }

    #[Route('/changer-password', name: 'app_enseignant_changer_password', methods: ['GET', 'POST'])]
    public function changerPassword(
        Request $request,
        AuthChecker $authChecker,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Check if user is a teacher
        if (!$authChecker->isEnseignant()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $authChecker->getCurrentUser();

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Get the form data
                $currentPassword = $form->get('currentPassword')->getData();
                $newPassword = $form->get('newPassword')->getData();

                // Verify current password
                if (!password_verify($currentPassword, $enseignant->getMotDePasse())) {
                    $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                } else {
                    // Hash new password
                    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                    $enseignant->setMotDePasse($hashedPassword);

                    $entityManager->flush();
                    $this->addFlash('success', 'Mot de passe changé avec succès !');

                    return $this->redirectToRoute('app_enseignant_changer_password');
                }
            } else {
                // Form has validation errors
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
            }
        }

        return $this->render('enseignant/changer_password.html.twig', [
            'form' => $form->createView(),
            'enseignant' => $enseignant,
        ]);
    }

    // ========== GENERIC {id} ROUTES ==========

    #[Route('/{id}', name: 'app_enseignant_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        EnseignantRepository $enseignantRepository,
        AuthChecker $authChecker
    ): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Only admins can see teacher details
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux administrateurs.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $enseignantRepository->find($id);

        if (!$enseignant) {
            $this->addFlash('error', 'Enseignant non trouvé.');
            return $this->redirectToRoute('app_administrateur_utilisateurs');
        }

        return $this->render('enseignant/show.html.twig', [
            'enseignant' => $enseignant,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_enseignant_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        int $id,
        EnseignantRepository $enseignantRepository,
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Only admins can edit teachers
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux administrateurs.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $enseignantRepository->find($id);

        if (!$enseignant) {
            $this->addFlash('error', 'Enseignant non trouvé.');
            return $this->redirectToRoute('app_administrateur_utilisateurs');
        }

        $form = $this->createForm(EnseignantType::class, $enseignant, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update password only if provided
            if ($form->has('motDePasse') && $form->get('motDePasse')->getData()) {
                $plainPassword = $form->get('motDePasse')->getData();
                $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
                $enseignant->setMotDePasse($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Enseignant modifié avec succès!');

            // If "save and continue" button was clicked, stay on the same page
            if ($request->request->has('save_and_continue')) {
                return $this->redirectToRoute('app_enseignant_edit', ['id' => $enseignant->getId()]);
            }

            // Otherwise redirect to the show page
            return $this->redirectToRoute('app_administrateur_utilisateur_show', [
                'type' => 'enseignant',
                'id' => $enseignant->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/enseignant_edit.html.twig', [
            'enseignant' => $enseignant,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_enseignant_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        Request $request,
        int $id,
        EnseignantRepository $enseignantRepository,
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Only admins can delete teachers
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux administrateurs.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $enseignantRepository->find($id);

        if (!$enseignant) {
            $this->addFlash('error', 'Enseignant non trouvé.');
            return $this->redirectToRoute('app_administrateur_utilisateurs');
        }

        if ($this->isCsrfTokenValid('delete'.$enseignant->getId(), $request->request->get('_token'))) {
            $entityManager->remove($enseignant);
            $entityManager->flush();

            $this->addFlash('success', 'Enseignant supprimé avec succès!');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_administrateur_utilisateurs', [], Response::HTTP_SEE_OTHER);
    }
}
