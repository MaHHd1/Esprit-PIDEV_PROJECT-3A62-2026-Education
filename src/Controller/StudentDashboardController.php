<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Entity\Etudiant;
use App\Form\ChangePasswordType;
use App\Repository\CoursTempsPasseRepository;
use App\Repository\ContenuProgressionRepository;
use App\Repository\CoursRepository;
use App\Repository\CoursVueRepository;
use App\Repository\ModuleRepository;
use App\Service\ActivityLogger;
use App\Service\AuthChecker;
use App\Service\CalendarIcsService;
use App\Service\CourseNotificationService;
use App\Service\HuggingFaceCourseAssistantService;
use App\Service\SimplePaginator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/etudiant')]
class StudentDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_etudiant_dashboard', methods: ['GET'])]
    public function dashboard(AuthChecker $authChecker, CoursRepository $coursRepository): Response
    {
        $student = $this->getAuthenticatedStudent($authChecker);
        if (!$student) {
            return $this->redirectToRoute('app_login');
        }

        $enrolledCourses = $coursRepository->findEnrolledByEtudiantId($student->getId());
        $availableCourses = $coursRepository->findAvailableForEtudiantId($student->getId());

        return $this->render('etudiant/dashboard.html.twig', [
            'student' => $student,
            'current_user' => $student,
            'is_etudiant' => true,
            'is_admin' => false,
            'is_enseignant' => false,
            'enrolled_count' => count($enrolledCourses),
            'available_count' => count($availableCourses),
            'recent_courses' => array_slice($enrolledCourses, 0, 5),
        ]);
    }

    #[Route('/change-password', name: 'app_etudiant_change_password', methods: ['GET', 'POST'])]
    public function studentChangePassword(
        Request $request,
        AuthChecker $authChecker,
        EntityManagerInterface $entityManager
    ): Response {
        $student = $this->getAuthenticatedStudent($authChecker);
        if (!$student) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $currentPassword = $form->get('currentPassword')->getData();
                $newPassword = $form->get('newPassword')->getData();

                if (!password_verify($currentPassword, $student->getMotDePasse())) {
                    $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                } else {
                    $student->setMotDePasse(password_hash($newPassword, PASSWORD_BCRYPT));
                    $entityManager->flush();
                    $this->addFlash('success', 'Votre mot de passe a ete change avec succes.');

                    return $this->redirectToRoute('app_etudiant_profile');
                }
            } else {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addFlash('error', !empty($errors) ? implode(' ', $errors) : 'Veuillez corriger les erreurs du formulaire.');
            }
        }

        return $this->render('etudiant/change_password.html.twig', [
            'form' => $form->createView(),
            'student' => $student,
            'current_user' => $student,
            'is_etudiant' => true,
            'is_admin' => false,
            'is_enseignant' => false,
        ]);
    }

    #[Route('/courses', name: 'app_etudiant_courses', methods: ['GET'])]
    public function courses(
        Request $request,
        AuthChecker $authChecker,
        CoursRepository $coursRepository,
        ModuleRepository $moduleRepository,
        SimplePaginator $paginator
    ): Response
    {
        $student = $this->getAuthenticatedStudent($authChecker);
        if (!$student) {
            return $this->redirectToRoute('app_login');
        }

        $filters = [
            'q' => trim((string) $request->query->get('q', '')),
            'module_id' => (string) $request->query->get('module_id', ''),
        ];

        $courses = $coursRepository->findEnrolledByEtudiantIdWithFilters((int) $student->getId(), $filters);
        $pagination = $paginator->paginateArray($courses, (int) $request->query->get('page', 1), 10);

        return $this->render('etudiant/courses.html.twig', [
            'student' => $student,
            'current_user' => $student,
            'is_etudiant' => true,
            'is_admin' => false,
            'is_enseignant' => false,
            'mode' => 'enrolled',
            'courses' => $pagination['items'],
            'filters' => $filters,
            'modules' => $moduleRepository->findBy([], ['titreModule' => 'ASC']),
            'pagination' => $pagination,
        ]);
    }

    #[Route('/courses/discover', name: 'app_etudiant_courses_discover', methods: ['GET'])]
    public function discoverCourses(
        Request $request,
        AuthChecker $authChecker,
        CoursRepository $coursRepository,
        ModuleRepository $moduleRepository,
        SimplePaginator $paginator
    ): Response
    {
        $student = $this->getAuthenticatedStudent($authChecker);
        if (!$student) {
            return $this->redirectToRoute('app_login');
        }

        $filters = [
            'q' => trim((string) $request->query->get('q', '')),
            'module_id' => (string) $request->query->get('module_id', ''),
        ];

        $courses = $coursRepository->findAvailableForEtudiantIdWithFilters((int) $student->getId(), $filters);
        $pagination = $paginator->paginateArray($courses, (int) $request->query->get('page', 1), 10);

        return $this->render('etudiant/courses.html.twig', [
            'student' => $student,
            'current_user' => $student,
            'is_etudiant' => true,
            'is_admin' => false,
            'is_enseignant' => false,
            'mode' => 'discover',
            'courses' => $pagination['items'],
            'filters' => $filters,
            'modules' => $moduleRepository->findBy([], ['titreModule' => 'ASC']),
            'pagination' => $pagination,
        ]);
    }

    #[Route('/courses/{id}/enroll', name: 'app_etudiant_course_enroll', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function enroll(
        int $id,
        Request $request,
        AuthChecker $authChecker,
        CoursRepository $coursRepository,
        EntityManagerInterface $entityManager,
        CourseNotificationService $notificationService,
        ActivityLogger $activityLogger
    ): Response {
        $student = $this->getAuthenticatedStudent($authChecker);
        if (!$student) {
            return $this->redirectToRoute('app_login');
        }

        $course = $coursRepository->find($id);
        if (!$course || $course->getStatut() !== 'ouvert') {
            $this->addFlash('error', 'Cours non disponible.');
            return $this->redirectToRoute('app_etudiant_courses_discover');
        }

        $prerequisIds = array_map('intval', array_filter((array) ($course->getPrerequis() ?? [])));
        if ($prerequisIds !== []) {
            $inscritIds = array_map(
                static fn (Cours $c): int => (int) $c->getId(),
                $student->getCoursInscrits()->toArray()
            );
            $manquants = array_values(array_diff($prerequisIds, $inscritIds));

            if ($manquants !== []) {
                $missingCourses = $coursRepository->findBy(['id' => $manquants]);
                $missingNames = array_map(static fn (Cours $c): string => (string) $c->getTitre(), $missingCourses);
                $this->addFlash('error', 'Prerequis manquants: ' . implode(', ', $missingNames));

                return $this->redirectToRoute('app_etudiant_courses_discover');
            }
        }

        if (!$this->isCsrfTokenValid('enroll_course_'.$course->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_etudiant_courses_discover');
        }

        if (!$student->isInscritAuCours($course)) {
            $student->addCoursInscrit($course);
            $entityManager->flush();
            $notificationService->notifyEnrollment($student, $course);
            $activityLogger->log($student, 'course_enroll', 'cours', (int) $course->getId(), [
                'course_code' => $course->getCodeCours(),
            ]);
            $this->addFlash('success', 'Inscription au cours effectuee.');
        }

        return $this->redirectToRoute('app_etudiant_courses');
    }

    #[Route('/courses/{id}/unenroll', name: 'app_etudiant_course_unenroll', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unenroll(
        int $id,
        Request $request,
        AuthChecker $authChecker,
        CoursRepository $coursRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $student = $this->getAuthenticatedStudent($authChecker);
        if (!$student) {
            return $this->redirectToRoute('app_login');
        }

        $course = $coursRepository->find($id);
        if (!$course) {
            $this->addFlash('error', 'Cours introuvable.');
            return $this->redirectToRoute('app_etudiant_courses');
        }

        if (!$this->isCsrfTokenValid('unenroll_course_'.$course->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_etudiant_courses');
        }

        if ($student->isInscritAuCours($course)) {
            $student->removeCoursInscrit($course);
            $entityManager->flush();
            $this->addFlash('success', 'Desinscription du cours effectuee.');
        }

        return $this->redirectToRoute('app_etudiant_courses');
    }

    #[Route('/courses/{id}', name: 'app_etudiant_course_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showCourse(
        int $id,
        AuthChecker $authChecker,
        CoursRepository $coursRepository,
        ContenuProgressionRepository $progressionRepository,
        CoursVueRepository $coursVueRepository,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response
    {
        $student = $this->getAuthenticatedStudent($authChecker);
        if (!$student) {
            return $this->redirectToRoute('app_login');
        }

        $course = $coursRepository->find($id);
        if (!$course || $course->getStatut() !== 'ouvert') {
            $this->addFlash('error', 'Cours non disponible.');
            return $this->redirectToRoute('app_etudiant_courses');
        }

        if (!$student->isInscritAuCours($course)) {
            $this->addFlash('error', 'Vous devez etre inscrit a ce cours pour le consulter.');
            return $this->redirectToRoute('app_etudiant_courses');
        }

        $existingView = $coursVueRepository->findOneBy([
            'etudiant' => $student,
            'cours' => $course,
        ]);

        if ($existingView === null) {
            $view = new \App\Entity\CoursVue();
            $view->setEtudiant($student);
            $view->setCours($course);
            $entityManager->persist($view);
            $entityManager->flush();
        }

        $contents = array_values(array_filter(
            $course->getContenus()->toArray(),
            static fn ($contenu) => $contenu->isEstPublic()
        ));

        usort($contents, static function ($a, $b) {
            return $a->getOrdreAffichage() <=> $b->getOrdreAffichage();
        });

        $completedIds = $progressionRepository->findCompletedContenuIds((int) $student->getId(), (int) $course->getId());
        $total = count($contents);
        $completed = count(array_intersect($completedIds, array_map(static fn ($c) => $c->getId(), $contents)));
        $progressPercent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return $this->render('etudiant/course_show.html.twig', [
            'student' => $student,
            'current_user' => $student,
            'is_etudiant' => true,
            'is_admin' => false,
            'is_enseignant' => false,
            'course' => $course,
            'contents' => $contents,
            'completed_ids' => $completedIds,
            'progress_percent' => $progressPercent,
            'completed_count' => $completed,
            'total_count' => $total,
            'track_time_token' => $csrfTokenManager->getToken('track_course_time_'.$course->getId())->getValue(),
        ]);
    }

    #[Route('/courses/{id}/track-time', name: 'app_etudiant_course_track_time', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function trackCourseTime(
        int $id,
        Request $request,
        AuthChecker $authChecker,
        CoursRepository $coursRepository,
        CoursTempsPasseRepository $coursTempsPasseRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $student = $this->getAuthenticatedStudent($authChecker);
        if (!$student) {
            return $this->json(['ok' => false, 'message' => 'Non authentifie.'], 401);
        }

        $course = $coursRepository->find($id);
        if (!$course || !$student->isInscritAuCours($course)) {
            return $this->json(['ok' => false, 'message' => 'Cours non autorise.'], 403);
        }

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('track_course_time_'.$course->getId(), $token)) {
            return $this->json(['ok' => false, 'message' => 'Token CSRF invalide.'], 400);
        }

        $seconds = (int) $request->request->get('seconds', 0);
        if ($seconds <= 0) {
            return $this->json(['ok' => false, 'message' => 'Duree invalide.'], 400);
        }

        $row = $coursTempsPasseRepository->addTime($student, $course, $seconds);
        $entityManager->flush();

        return $this->json([
            'ok' => true,
            'minutes' => $row->getSecondes() > 0 ? (int) ceil($row->getSecondes() / 60) : 0,
        ]);
    }

    #[Route('/courses/{courseId}/contents/{contenuId}/complete', name: 'app_etudiant_content_complete', methods: ['POST'], requirements: ['courseId' => '\d+', 'contenuId' => '\d+'])]
    public function completeContent(
        int $courseId,
        int $contenuId,
        Request $request,
        AuthChecker $authChecker,
        CoursRepository $coursRepository,
        EntityManagerInterface $entityManager,
        ContenuProgressionRepository $progressionRepository,
        ActivityLogger $activityLogger
    ): Response|JsonResponse {
        $student = $this->getAuthenticatedStudent($authChecker);
        if (!$student) {
            return $this->redirectToRoute('app_login');
        }

        $course = $coursRepository->find($courseId);
        if (!$course || !$student->isInscritAuCours($course)) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['ok' => false, 'message' => 'Cours non autorise.'], 403);
            }
            $this->addFlash('error', 'Cours non autorise.');
            return $this->redirectToRoute('app_etudiant_courses');
        }

        if (
            !$request->isXmlHttpRequest()
            && !$this->isCsrfTokenValid('complete_content_'.$contenuId, (string) $request->request->get('_token'))
        ) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['ok' => false, 'message' => 'Token CSRF invalide.'], 400);
            }
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_etudiant_course_show', ['id' => $courseId]);
        }

        $target = null;
        foreach ($course->getContenus() as $contenu) {
            if ((int) $contenu->getId() === $contenuId && $contenu->isEstPublic()) {
                $target = $contenu;
                break;
            }
        }

        if ($target === null) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['ok' => false, 'message' => 'Contenu introuvable.'], 404);
            }
            $this->addFlash('error', 'Contenu introuvable.');
            return $this->redirectToRoute('app_etudiant_course_show', ['id' => $courseId]);
        }

        $progress = $progressionRepository->findOneBy([
            'etudiant' => $student,
            'cours' => $course,
            'contenu' => $target,
        ]);

        $newlyCompleted = false;

        if ($progress === null) {
            $progress = new \App\Entity\ContenuProgression();
            $progress->setEtudiant($student);
            $progress->setCours($course);
            $progress->setContenu($target);
            $entityManager->persist($progress);
            $newlyCompleted = true;
        } elseif (!$progress->isEstTermine()) {
            $newlyCompleted = true;
        }

        $progress->setEstTermine(true);
        $progress->setDateTerminee(new \DateTime());
        $entityManager->flush();

        $activityLogger->log($student, 'content_complete', 'contenu', (int) $target->getId(), [
            'course_id' => $courseId,
        ]);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'ok' => true,
                'newly_completed' => $newlyCompleted,
                'message' => 'Contenu marque comme termine.',
            ]);
        }

        $this->addFlash('success', 'Contenu marque comme termine.');
        return $this->redirectToRoute('app_etudiant_course_show', ['id' => $courseId]);
    }

    #[Route('/courses/{id}/calendar.ics', name: 'app_etudiant_course_calendar', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function exportCalendar(
        int $id,
        AuthChecker $authChecker,
        CoursRepository $coursRepository,
        CalendarIcsService $calendarIcsService
    ): Response {
        $student = $this->getAuthenticatedStudent($authChecker);
        if (!$student) {
            return $this->redirectToRoute('app_login');
        }

        $course = $coursRepository->find($id);
        if (!$course || !$student->isInscritAuCours($course)) {
            $this->addFlash('error', 'Cours non autorise.');
            return $this->redirectToRoute('app_etudiant_courses');
        }

        $ics = $calendarIcsService->generateCourseIcs($course);
        $response = new Response($ics);
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            sprintf('cours-%d.ics', $course->getId())
        ));

        return $response;
    }

    #[Route('/grades', name: 'app_etudiant_grades', methods: ['GET'])]
    public function grades(AuthChecker $authChecker): Response
    {
        $student = $this->getAuthenticatedStudent($authChecker);
        if (!$student) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('etudiant/grades.html.twig', [
            'student' => $student,
            'current_user' => $student,
            'is_etudiant' => true,
            'is_admin' => false,
            'is_enseignant' => false,
        ]);
    }

    #[Route('/assistant-cours', name: 'app_etudiant_course_assistant', methods: ['GET'])]
    public function courseAssistant(
        AuthChecker $authChecker,
        CoursRepository $coursRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response
    {
        $student = $this->getAuthenticatedStudent($authChecker);
        if (!$student) {
            return $this->redirectToRoute('app_login');
        }

        $courses = $coursRepository->findEnrolledByEtudiantId((int) $student->getId());

        return $this->render('etudiant/course_assistant.html.twig', [
            'student' => $student,
            'current_user' => $student,
            'is_etudiant' => true,
            'is_admin' => false,
            'is_enseignant' => false,
            'courses' => $courses,
            'assistant_csrf_token' => $csrfTokenManager->getToken('student_course_assistant')->getValue(),
        ]);
    }

    #[Route('/assistant-cours/ask', name: 'app_etudiant_course_assistant_ask', methods: ['POST'])]
    public function askCourseAssistant(
        Request $request,
        AuthChecker $authChecker,
        CoursRepository $coursRepository,
        HuggingFaceCourseAssistantService $assistantService
    ): JsonResponse {
        $student = $this->getAuthenticatedStudent($authChecker);
        if (!$student) {
            return $this->json(['ok' => false, 'message' => 'Non authentifie.'], 401);
        }

        $token = (string) $request->headers->get('X-CSRF-TOKEN', '');
        if (!$this->isCsrfTokenValid('student_course_assistant', $token)) {
            return $this->json(['ok' => false, 'message' => 'Token CSRF invalide.'], 400);
        }

        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['ok' => false, 'message' => 'Payload invalide.'], 400);
        }

        $courseId = (int) ($payload['courseId'] ?? 0);
        $question = trim((string) ($payload['question'] ?? ''));

        if ($courseId <= 0 || $question === '') {
            return $this->json(['ok' => false, 'message' => 'Cours et question sont obligatoires.'], 400);
        }

        $course = $coursRepository->find($courseId);
        if (!$course || !$student->isInscritAuCours($course)) {
            return $this->json(['ok' => false, 'message' => 'Cours non autorise.'], 403);
        }

        $contentLines = [];
        foreach ($course->getContenus() as $contenu) {
            if (!$contenu->isEstPublic()) {
                continue;
            }
            $line = trim((string) $contenu->getTitre());
            $description = trim((string) ($contenu->getDescription() ?? ''));
            if ($description !== '') {
                $line .= ' - ' . mb_substr($description, 0, 180);
            }
            if ($line !== '') {
                $contentLines[] = $line;
            }
        }

        $result = $assistantService->askAboutCourse($course, $question, array_slice($contentLines, 0, 25));
        if (!$result['ok']) {
            return $this->json([
                'ok' => false,
                'message' => $result['error'] ?? 'Erreur assistant.',
            ], 500);
        }

        return $this->json([
            'ok' => true,
            'answer' => $result['answer'],
        ]);
    }

    private function getAuthenticatedStudent(AuthChecker $authChecker): ?Etudiant
    {
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter.');
            return null;
        }

        $user = $authChecker->getCurrentUser();
        if (!$user instanceof Etudiant) {
            $this->addFlash('error', 'Acces non autorise.');
            return null;
        }

        return $user;
    }
}
