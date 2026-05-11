<?php

namespace App\Controller;

use App\Entity\Contenu;
use App\Entity\Cours;
use App\Entity\Enseignant;
use App\Form\ContenuType;
use App\Form\CoursType;
use App\Repository\ContenuProgressionRepository;
use App\Repository\ContenuRepository;
use App\Repository\CoursRepository;
use App\Repository\CoursVueRepository;
use App\Repository\QuizRepository;
use App\Service\ActivityLogger;
use App\Service\AuthChecker;
use App\Service\CourseNotificationService;
use App\Service\SimplePaginator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/enseignant/mes-cours')]
class EnseignantCoursController extends AbstractController
{
    #[Route('', name: 'app_enseignant_mes_cours', methods: ['GET'])]
    public function index(
        Request $request,
        AuthChecker $authChecker,
        CoursRepository $coursRepository,
        ContenuProgressionRepository $progressionRepository,
        CoursVueRepository $coursVueRepository,
        SimplePaginator $paginator
    ): Response
    {
        $enseignant = $this->getAuthenticatedTeacher($authChecker);
        if (!$enseignant) {
            return $this->redirectToRoute('app_home');
        }

        $filters = [
            'q' => trim((string) $request->query->get('q', '')),
            'statut' => (string) $request->query->get('statut', ''),
            'module_id' => (string) $request->query->get('module_id', ''),
        ];

        $cours = $coursRepository->findByEnseignantIdWithFilters((int) $enseignant->getId(), $filters);
        $pagination = $paginator->paginateArray($cours, (int) $request->query->get('page', 1), 10);
        $cours = $pagination['items'];
        $allCours = $coursRepository->findByEnseignantId((int) $enseignant->getId());
        $modulesById = [];

        foreach ($allCours as $courseItem) {
            $module = $courseItem->getModule();
            if ($module && !isset($modulesById[$module->getId()])) {
                $modulesById[$module->getId()] = $module;
            }
        }

        $enrolledByCourse = [];
        $totalStudents = 0;
        $totalPublicContents = 0;
        $completedCount = 0;

        foreach ($allCours as $courseItem) {
            $countStudents = $courseItem->getEtudiants()->count();
            $enrolledByCourse[] = [
                'course' => $courseItem,
                'count' => $countStudents,
            ];
            $totalStudents += $countStudents;

            foreach ($courseItem->getContenus() as $contenu) {
                if ($contenu->isEstPublic()) {
                    $totalPublicContents++;
                }
            }
        }
        $topViewedCourses = $coursVueRepository->findTopViewedCoursesByEnseignant((int) $enseignant->getId(), 5);

        foreach ($allCours as $courseItem) {
            $completedCount += $progressionRepository->countCompletedByCours((int) $courseItem->getId());
        }

        $activityRate = ($totalStudents > 0 && $totalPublicContents > 0)
            ? (int) round(($completedCount / max(1, $totalStudents * $totalPublicContents)) * 100)
            : 0;

        return $this->render('enseignant/mes_cours.html.twig', [
            'enseignant' => $enseignant,
            'cours' => $cours,
            'filters' => $filters,
            'modules' => array_values($modulesById),
            'enrolled_by_course' => $enrolledByCourse,
            'top_viewed_courses' => $topViewedCourses,
            'activity_rate' => $activityRate,
            'total_students' => $totalStudents,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/nouveau', name: 'app_enseignant_mes_cours_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        AuthChecker $authChecker,
        EntityManagerInterface $entityManager,
        ActivityLogger $activityLogger
    ): Response
    {
        $enseignant = $this->getAuthenticatedTeacher($authChecker);
        if (!$enseignant) {
            return $this->redirectToRoute('app_home');
        }

        $cours = new Cours();
        $form = $this->createForm(CoursType::class, $cours, [
            'show_enseignants' => false,
            'prerequis_data' => '',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cours->addEnseignant($enseignant);
            $cours->setPrerequis($this->parsePrerequisIds((string) $form->get('prerequisIds')->getData()));

            $entityManager->persist($cours);
            $entityManager->flush();
            $activityLogger->log($enseignant, 'course_create', 'cours', (int) $cours->getId(), [
                'code' => $cours->getCodeCours(),
            ]);

            $this->addFlash('success', 'Cours cree avec succes.');
            return $this->redirectToRoute('app_enseignant_mes_cours');
        }

        return $this->render('enseignant/cours_new.html.twig', [
            'enseignant' => $enseignant,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/contenus', name: 'app_enseignant_mes_cours_contenus', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function contenus(
        int $id,
        Request $request,
        AuthChecker $authChecker,
        CoursRepository $coursRepository,
        \App\Service\SimplePaginator $paginator
    ): Response
    {
        $enseignant = $this->getAuthenticatedTeacher($authChecker);
        if (!$enseignant) {
            return $this->redirectToRoute('app_home');
        }

        $cours = $coursRepository->find($id);
        if (!$cours || !$coursRepository->isAssignedToEnseignant($id, $enseignant->getId())) {
            $this->addFlash('error', 'Acces non autorise a ce cours.');
            return $this->redirectToRoute('app_enseignant_mes_cours');
        }

        $contenus = $cours->getContenus()->toArray();
        usort($contenus, static fn (Contenu $a, Contenu $b) => $a->getOrdreAffichage() <=> $b->getOrdreAffichage());
        $pagination = $paginator->paginateArray($contenus, (int) $request->query->get('page', 1), 12);

        return $this->render('enseignant/cours_contenus.html.twig', [
            'enseignant' => $enseignant,
            'cours' => $cours,
            'contenus' => $pagination['items'],
            'pagination' => $pagination,
        ]);
    }

    #[Route('/{id}/visibilite', name: 'app_enseignant_mes_cours_toggle_visibility', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleCourseVisibility(
        int $id,
        Request $request,
        AuthChecker $authChecker,
        CoursRepository $coursRepository,
        EntityManagerInterface $entityManager,
        CourseNotificationService $notificationService,
        ActivityLogger $activityLogger
    ): Response {
        $enseignant = $this->getAuthenticatedTeacher($authChecker);
        if (!$enseignant) {
            return $this->redirectToRoute('app_home');
        }

        $cours = $coursRepository->find($id);
        if (!$cours || !$coursRepository->isAssignedToEnseignant($id, $enseignant->getId())) {
            $this->addFlash('error', 'Acces non autorise a ce cours.');
            return $this->redirectToRoute('app_enseignant_mes_cours');
        }

        if (!$this->isCsrfTokenValid('toggle_course_visibility'.$cours->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_enseignant_mes_cours');
        }

        $isVisible = $cours->getStatut() === 'ouvert';
        if ($isVisible) {
            // Masquage du cours: tous les contenus deviennent masques par defaut.
            $cours->setStatut('brouillon');
            foreach ($cours->getContenus() as $contenu) {
                $contenu->setEstPublic(false);
            }
            $this->addFlash('success', 'Cours masque. Tous les contenus ont ete masques.');
        } else {
            $cours->setStatut('ouvert');
            $this->addFlash('success', 'Cours affiche pour les etudiants.');
        }

        $entityManager->flush();
        $notificationService->notifyCourseVisibilityChanged($cours, !$isVisible);
        $activityLogger->log($enseignant, 'course_visibility_toggle', 'cours', (int) $cours->getId(), [
            'visible' => !$isVisible,
            'status' => $cours->getStatut(),
        ]);

        return $this->redirectToRoute('app_enseignant_mes_cours');
    }

    #[Route('/{id}/contenus/nouveau', name: 'app_enseignant_mes_cours_contenus_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function newContenu(
        int $id,
        Request $request,
        AuthChecker $authChecker,
        CoursRepository $coursRepository,
        QuizRepository $quizRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        ActivityLogger $activityLogger
    ): Response {
        $enseignant = $this->getAuthenticatedTeacher($authChecker);
        if (!$enseignant) {
            return $this->redirectToRoute('app_home');
        }

        $cours = $coursRepository->find($id);
        if (!$cours || !$coursRepository->isAssignedToEnseignant($id, $enseignant->getId())) {
            $this->addFlash('error', 'Acces non autorise a ce cours.');
            return $this->redirectToRoute('app_enseignant_mes_cours');
        }

        $contenu = new Contenu();
        $contenu->setCours($cours);

        $form = $this->createForm(ContenuType::class, $contenu, [
            'types_data' => ['texte'],
            'resources_data' => [],
            'cours_choices' => [$cours],
            'cours_disabled' => true,
            'quiz_choices' => $this->buildQuizChoices(
                $quizRepository->findForContentForm((int) $enseignant->getId(), (int) $cours->getId()),
                null
            ),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->hydrateResourcesFromForm(
                $form,
                $contenu,
                $slugger,
                false,
                $entityManager,
                $quizRepository,
                (int) $enseignant->getId()
            )) {
                $entityManager->persist($contenu);
                $entityManager->flush();
                $activityLogger->log($enseignant, 'content_create', 'contenu', (int) $contenu->getId(), [
                    'course_id' => $cours->getId(),
                ]);

                $this->addFlash('success', 'Contenu ajoute avec succes.');
                return $this->redirectToRoute('app_enseignant_mes_cours_contenus', ['id' => $cours->getId()]);
            }
        }

        return $this->render('enseignant/contenu_new.html.twig', [
            'enseignant' => $enseignant,
            'cours' => $cours,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/contenu/{contenuId}', name: 'app_enseignant_mes_cours_contenu_show', methods: ['GET'], requirements: ['contenuId' => '\d+'])]
    public function showContenu(
        int $contenuId,
        AuthChecker $authChecker,
        ContenuRepository $contenuRepository,
        CoursRepository $coursRepository
    ): Response {
        $enseignant = $this->getAuthenticatedTeacher($authChecker);
        if (!$enseignant) {
            return $this->redirectToRoute('app_home');
        }

        $contenu = $this->getOwnedContenu($contenuId, $enseignant, $contenuRepository, $coursRepository);
        if (!$contenu) {
            $this->addFlash('error', 'Contenu introuvable ou non autorise.');
            return $this->redirectToRoute('app_enseignant_mes_cours');
        }

        return $this->render('enseignant/contenu_show.html.twig', [
            'enseignant' => $enseignant,
            'contenu' => $contenu,
        ]);
    }

    #[Route('/contenu/{contenuId}/modifier', name: 'app_enseignant_mes_cours_contenu_edit', methods: ['GET', 'POST'], requirements: ['contenuId' => '\d+'])]
    public function editContenu(
        int $contenuId,
        Request $request,
        AuthChecker $authChecker,
        ContenuRepository $contenuRepository,
        CoursRepository $coursRepository,
        QuizRepository $quizRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        ActivityLogger $activityLogger
    ): Response {
        $enseignant = $this->getAuthenticatedTeacher($authChecker);
        if (!$enseignant) {
            return $this->redirectToRoute('app_home');
        }

        $contenu = $this->getOwnedContenu($contenuId, $enseignant, $contenuRepository, $coursRepository);
        if (!$contenu) {
            $this->addFlash('error', 'Contenu introuvable ou non autorise.');
            return $this->redirectToRoute('app_enseignant_mes_cours');
        }

        $cours = $contenu->getCours();
        $form = $this->createForm(ContenuType::class, $contenu, [
            'types_data' => $contenu->getTypeContenuList(),
            'resources_data' => $contenu->getRessourcesForDisplay(),
            'cours_choices' => $cours ? [$cours] : [],
            'cours_disabled' => true,
            'quiz_choices' => $this->buildQuizChoices(
                $quizRepository->findForContentForm((int) $enseignant->getId(), $cours?->getId()),
                $contenu->getRessourcesForDisplay()['quiz_id'] ?? null
            ),
            'quiz_selected_data' => $contenu->getRessourcesForDisplay()['quiz_id'] ?? null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->hydrateResourcesFromForm(
                $form,
                $contenu,
                $slugger,
                true,
                $entityManager,
                $quizRepository,
                (int) $enseignant->getId()
            )) {
                $entityManager->flush();
                $activityLogger->log($enseignant, 'content_edit', 'contenu', (int) $contenu->getId(), [
                    'course_id' => $cours?->getId(),
                ]);
                $this->addFlash('success', 'Contenu modifie avec succes.');

                return $this->redirectToRoute('app_enseignant_mes_cours_contenu_show', ['contenuId' => $contenu->getId()]);
            }
        }

        return $this->render('enseignant/contenu_edit.html.twig', [
            'enseignant' => $enseignant,
            'contenu' => $contenu,
            'cours' => $cours,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/contenu/{contenuId}/supprimer', name: 'app_enseignant_mes_cours_contenu_delete', methods: ['POST'], requirements: ['contenuId' => '\d+'])]
    public function deleteContenu(
        int $contenuId,
        Request $request,
        AuthChecker $authChecker,
        ContenuRepository $contenuRepository,
        CoursRepository $coursRepository,
        EntityManagerInterface $entityManager,
        ActivityLogger $activityLogger
    ): Response {
        $enseignant = $this->getAuthenticatedTeacher($authChecker);
        if (!$enseignant) {
            return $this->redirectToRoute('app_home');
        }

        $contenu = $this->getOwnedContenu($contenuId, $enseignant, $contenuRepository, $coursRepository);
        if (!$contenu) {
            $this->addFlash('error', 'Contenu introuvable ou non autorise.');
            return $this->redirectToRoute('app_enseignant_mes_cours');
        }

        if (!$this->isCsrfTokenValid('delete'.$contenu->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_enseignant_mes_cours_contenu_show', ['contenuId' => $contenu->getId()]);
        }

        $coursId = $contenu->getCours()?->getId();
        $deletedId = (int) $contenu->getId();
        $this->cleanupUploadedResources($contenu->getRessourcesForDisplay());
        $entityManager->remove($contenu);
        $entityManager->flush();
        $activityLogger->log($enseignant, 'content_delete', 'contenu', $deletedId, [
            'course_id' => $coursId,
        ]);

        $this->addFlash('success', 'Contenu supprime avec succes.');
        if ($coursId) {
            return $this->redirectToRoute('app_enseignant_mes_cours_contenus', ['id' => $coursId]);
        }

        return $this->redirectToRoute('app_enseignant_mes_cours');
    }

    #[Route('/contenu/{contenuId}/visibilite', name: 'app_enseignant_mes_cours_contenu_toggle_visibility', methods: ['POST'], requirements: ['contenuId' => '\d+'])]
    public function toggleContenuVisibility(
        int $contenuId,
        Request $request,
        AuthChecker $authChecker,
        ContenuRepository $contenuRepository,
        CoursRepository $coursRepository,
        EntityManagerInterface $entityManager,
        ActivityLogger $activityLogger
    ): Response {
        $enseignant = $this->getAuthenticatedTeacher($authChecker);
        if (!$enseignant) {
            return $this->redirectToRoute('app_home');
        }

        $contenu = $this->getOwnedContenu($contenuId, $enseignant, $contenuRepository, $coursRepository);
        if (!$contenu) {
            $this->addFlash('error', 'Contenu introuvable ou non autorise.');
            return $this->redirectToRoute('app_enseignant_mes_cours');
        }

        if (!$this->isCsrfTokenValid('toggle_contenu_visibility'.$contenu->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_enseignant_mes_cours_contenus', ['id' => $contenu->getCours()->getId()]);
        }

        if ($contenu->getCours() && $contenu->getCours()->getStatut() !== 'ouvert') {
            $this->addFlash('error', 'Impossible d\'afficher un contenu tant que le cours est masque.');
            return $this->redirectToRoute('app_enseignant_mes_cours_contenus', ['id' => $contenu->getCours()->getId()]);
        }

        $contenu->setEstPublic(!$contenu->isEstPublic());
        $entityManager->flush();
        $activityLogger->log($enseignant, 'content_visibility_toggle', 'contenu', (int) $contenu->getId(), [
            'visible' => $contenu->isEstPublic(),
            'course_id' => $contenu->getCours()->getId(),
        ]);

        $this->addFlash('success', $contenu->isEstPublic() ? 'Contenu affiche pour les etudiants.' : 'Contenu masque pour les etudiants.');
        return $this->redirectToRoute('app_enseignant_mes_cours_contenus', ['id' => $contenu->getCours()->getId()]);
    }

    private function getOwnedContenu(
        int $contenuId,
        Enseignant $enseignant,
        ContenuRepository $contenuRepository,
        CoursRepository $coursRepository
    ): ?Contenu {
        $contenu = $contenuRepository->find($contenuId);
        if (!$contenu || !$contenu->getCours()) {
            return null;
        }

        if (!$coursRepository->isAssignedToEnseignant($contenu->getCours()->getId(), $enseignant->getId())) {
            return null;
        }

        return $contenu;
    }

    private function getAuthenticatedTeacher(AuthChecker $authChecker): ?Enseignant
    {
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter.');
            return null;
        }

        if (!$authChecker->isEnseignant()) {
            $this->addFlash('error', 'Acces non autorise.');
            return null;
        }

        $user = $authChecker->getCurrentUser();
        if (!$user instanceof Enseignant) {
            $this->addFlash('error', 'Utilisateur invalide.');
            return null;
        }

        return $user;
    }

    private function hydrateResourcesFromForm(
        FormInterface $form,
        Contenu $contenu,
        SluggerInterface $slugger,
        bool $preserveExisting,
        EntityManagerInterface $entityManager,
        QuizRepository $quizRepository,
        int $creatorId
    ): bool
    {
        $types = array_values(array_unique(array_filter((array) $form->get('typeContenu')->getData())));

        if ($types === []) {
            $form->get('typeContenu')->addError(new FormError('Selectionnez au moins un type de contenu.'));
            return false;
        }

        $existing = $preserveExisting ? $contenu->getRessourcesForDisplay() : [];
        $resources = [];

        $externalUrl = trim((string) $form->get('urlContenu')->getData());
        $externalUrl = $externalUrl !== '' ? $externalUrl : null;

        if ($externalUrl !== null && filter_var($externalUrl, FILTER_VALIDATE_URL) === false) {
            $form->get('urlContenu')->addError(new FormError('Le lien externe doit etre une URL valide.'));
            return false;
        }

        $textContent = trim((string) $form->get('texteContenu')->getData());
        if ($textContent !== '') {
            $resources['texte'] = $textContent;
        } elseif ($preserveExisting && in_array('texte', $types, true) && !empty($existing['texte'])) {
            $resources['texte'] = $existing['texte'];
        }

        if (in_array('lien', $types, true)) {
            if ($externalUrl !== null) {
                $resources['lien'] = $externalUrl;
            } elseif ($preserveExisting && !empty($existing['lien'])) {
                $resources['lien'] = $existing['lien'];
            } else {
                $form->get('urlContenu')->addError(new FormError('Un lien est requis quand le type Lien est selectionne.'));
                return false;
            }
        }

        if (in_array('video', $types, true)) {
            $videoPath = $this->handleUpload($form->get('videoFile')->getData(), $slugger, 'video');
            if ($videoPath !== null) {
                $resources['video_file'] = $videoPath;
            } elseif ($preserveExisting && !empty($existing['video_file'])) {
                $resources['video_file'] = $existing['video_file'];
            }

            if ($externalUrl !== null) {
                $resources['video_link'] = $externalUrl;
            } elseif ($preserveExisting && !empty($existing['video_link'])) {
                $resources['video_link'] = $existing['video_link'];
            }

            if (empty($resources['video_file']) && empty($resources['video_link'])) {
                $form->get('videoFile')->addError(new FormError('Ajoutez un fichier video ou un lien video.'));
                return false;
            }
        }

        if (in_array('pdf', $types, true)) {
            $pdfPath = $this->handleUpload($form->get('pdfFile')->getData(), $slugger, 'pdf');
            if ($pdfPath !== null) {
                $resources['pdf'] = $pdfPath;
            } elseif ($preserveExisting && !empty($existing['pdf'])) {
                $resources['pdf'] = $existing['pdf'];
            } else {
                $form->get('pdfFile')->addError(new FormError('Ajoutez un fichier PDF.'));
                return false;
            }
        }

        if (in_array('ppt', $types, true)) {
            $pptPath = $this->handleUpload($form->get('pptFile')->getData(), $slugger, 'ppt');
            if ($pptPath !== null) {
                $resources['ppt'] = $pptPath;
            } elseif ($preserveExisting && !empty($existing['ppt'])) {
                $resources['ppt'] = $existing['ppt'];
            } else {
                $form->get('pptFile')->addError(new FormError('Ajoutez un fichier PPT/PPTX.'));
                return false;
            }
        }

        if (in_array('texte', $types, true) && empty($resources['texte'])) {
            $form->get('texteContenu')->addError(new FormError('Saisissez un contenu texte.'));
            return false;
        }

        if (in_array('quiz', $types, true)) {
            $quizId = $form->get('quizExistingId')->getData();
            if (empty($quizId) && $preserveExisting && !empty($existing['quiz_id'])) {
                $quizId = (int) $existing['quiz_id'];
            }

            if (empty($quizId)) {
                $form->get('quizExistingId')->addError(new FormError('Selectionnez un quiz existant depuis Mes Quiz.'));
                return false;
            }

            $quiz = $quizRepository->find((int) $quizId);
            if (!$quiz || (int) $quiz->getIdCreateur() !== $creatorId) {
                $form->get('quizExistingId')->addError(new FormError('Quiz introuvable ou non autorise.'));
                return false;
            }

            if ($quiz->getIdCours() === null && $contenu->getCours()) {
                $quiz->setIdCours($contenu->getCours()->getId());
                $entityManager->flush();
            }

            $resources['quiz_id'] = $quiz->getId();
            $resources['quiz_title'] = $quiz->getTitre();
        }

        $contenu->setTypeContenuFromArray($types);
        $contenu->setRessources($resources);
        $contenu->setUrlContenu($resources['lien'] ?? $resources['video_link'] ?? null);

        return true;
    }

    /**
     * @param \App\Entity\Quiz[] $quizzes
     * @return array<string,int>
     */
    private function buildQuizChoices(array $quizzes, ?int $selectedId): array
    {
        $choices = [];
        foreach ($quizzes as $quiz) {
            $label = sprintf(
                '#%d - %s%s',
                (int) $quiz->getId(),
                (string) ($quiz->getTitre() ?? 'Sans titre'),
                $quiz->getIdCours() ? ' (Cours ' . $quiz->getIdCours() . ')' : ''
            );
            $choices[$label] = (int) $quiz->getId();
        }

        if ($selectedId !== null && $selectedId > 0 && !in_array($selectedId, $choices, true)) {
            $choices['#' . $selectedId . ' - Quiz associe'] = $selectedId;
        }

        return $choices;
    }

    private function handleUpload(?UploadedFile $file, SluggerInterface $slugger, string $prefix): ?string
    {
        if (!$file instanceof UploadedFile) {
            return null;
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/contenu';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $slugger->slug($originalName)->lower()->toString();
        $filename = sprintf('%s-%s-%s.%s', $prefix, $safeName, uniqid('', true), $file->guessExtension() ?: $file->getClientOriginalExtension());

        $file->move($uploadDir, $filename);

        return '/uploads/contenu/' . $filename;
    }

    /**
     * @param array<string,mixed> $resources
     */
    private function cleanupUploadedResources(array $resources): void
    {
        foreach (['pdf', 'ppt', 'video_file'] as $key) {
            $path = $resources[$key] ?? null;
            if (!is_string($path) || !str_starts_with($path, '/uploads/contenu/')) {
                continue;
            }
            $full = $this->getParameter('kernel.project_dir') . '/public' . $path;
            if (is_file($full)) {
                @unlink($full);
            }
        }
    }

    /**
     * @return int[]
     */
    private function parsePrerequisIds(string $raw): array
    {
        $parts = array_filter(array_map('trim', explode(',', $raw)));
        $ids = [];
        foreach ($parts as $part) {
            if (ctype_digit($part)) {
                $ids[] = (int) $part;
            }
        }

        return array_values(array_unique($ids));
    }
}
