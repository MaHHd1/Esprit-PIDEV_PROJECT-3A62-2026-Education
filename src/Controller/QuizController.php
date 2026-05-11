<?php

namespace App\Controller;

use App\Entity\Enseignant;
use App\Entity\Etudiant;
use App\Entity\Quiz;
use App\Entity\ResultatQuiz;
use App\Form\QuizType;
use App\Repository\CoursRepository;
use App\Repository\EtudiantRepository;
use App\Repository\QuizRepository;
use App\Repository\ResultatQuizRepository;
use App\Service\AuthChecker;
use App\Service\NotificationService;
use App\Service\QuizService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/quiz')]
class QuizController extends AbstractController
{
    public function __construct(
        private QuizService            $quizService,
        private QuizRepository         $quizRepository,
        private ResultatQuizRepository $resultatQuizRepository,
        private EntityManagerInterface $entityManager,
        private AuthChecker            $authChecker,
        private PaginatorInterface     $paginator,
        private NotificationService    $notificationService
    ) {}

    // ===========================
    // TEACHER ROUTES
    // ===========================

    #[Route('/nouveau', name: 'quiz_new', methods: ['GET', 'POST'])]
    public function new(Request $request, AuthChecker $authChecker, CoursRepository $coursRepository): Response
    {
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour créer un quiz.');
            return $this->redirectToRoute('app_login');
        }
        if (!$authChecker->isEnseignant()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $authChecker->getCurrentUser();
        $quiz = new Quiz();

        $coursList = $coursRepository->findAll();
        $coursChoices = [];
        foreach ($coursList as $c) {
            $coursChoices[$c->getTitre()] = $c->getId();
        }

        $form = $this->createForm(QuizType::class, $quiz, [
            'cours_choices' => $coursChoices,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $quiz->setIdCreateur($enseignant->getId());
                $quiz->setDateCreation(new \DateTime());
                $this->entityManager->persist($quiz);
                $this->entityManager->flush();

                try {
                    $this->notificationService->notifierEtudiantsNouveauQuiz(
                        enseignantUsername: $enseignant->getUsername(),
                        quizTitre:         $quiz->getTitre(),
                        quizId:            $quiz->getId(),
                        niveau:            (string) ($quiz->getDifficulteMoyenne() ?? 'moyen')
                    );
                } catch (\Exception) {}

                $this->addFlash('success', 'Le quiz a été créé avec succès !');
                return $this->redirectToRoute('teacher_question_new', ['idQuiz' => $quiz->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du quiz : ' . $e->getMessage());
            }
        }

        return $this->render('enseignant/quiz/new.html.twig', [
            'quiz'          => $quiz,
            'form'          => $form,
            'enseignant'    => $enseignant,
            'current_user'  => $enseignant,
            'is_etudiant'   => false,
            'is_admin'      => false,
            'is_enseignant' => true,
        ]);
    }

    #[Route('/teacher/quiz', name: 'teacher_quiz_index', methods: ['GET'])]
    public function teacherIndex(Request $request, AuthChecker $authChecker, CoursRepository $coursRepository): Response
    {
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour accéder à vos quiz.');
            return $this->redirectToRoute('app_login');
        }
        if (!$authChecker->isEnseignant()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $authChecker->getCurrentUser();
        $search     = $request->query->get('search', '');
        $type       = $request->query->get('type', '');
        $difficulte = $request->query->get('difficulte', '');
        $sort       = $request->query->get('sort', 'recent');

        $allowedSorts = ['recent', 'titre', 'titre_desc', 'difficulte_asc', 'difficulte_desc'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'recent';
        }

        try {
            $qb = $this->quizRepository->createQueryBuilder('q');
            $qb->andWhere('q.idCreateur = :userId')->setParameter('userId', $enseignant->getId());

            if (!empty($search)) {
                $qb->andWhere($qb->expr()->orX(
                    'q.titre LIKE :search',
                    'q.description LIKE :search',
                    'q.typeQuiz LIKE :search',
                    'q.instructions LIKE :search'
                ))->setParameter('search', '%' . trim($search) . '%');
            }

            if (!empty($type) && in_array($type, ['evaluation', 'entrainement', 'revision', 'diagnostique'])) {
                $qb->andWhere('q.typeQuiz = :type')->setParameter('type', $type);
            }

            if (!empty($difficulte)) {
                match ($difficulte) {
                    'facile'    => $qb->andWhere('q.difficulteMoyenne >= 1 AND q.difficulteMoyenne <= 3'),
                    'moyen'     => $qb->andWhere('q.difficulteMoyenne > 3 AND q.difficulteMoyenne <= 6'),
                    'difficile' => $qb->andWhere('q.difficulteMoyenne > 6 AND q.difficulteMoyenne <= 10'),
                    default     => null,
                };
            }

            match ($sort) {
                'titre'           => $qb->orderBy('q.titre', 'ASC'),
                'titre_desc'      => $qb->orderBy('q.titre', 'DESC'),
                'difficulte_asc'  => $qb->orderBy('q.difficulteMoyenne', 'ASC')->addOrderBy('q.titre', 'ASC'),
                'difficulte_desc' => $qb->orderBy('q.difficulteMoyenne', 'DESC')->addOrderBy('q.titre', 'ASC'),
                default           => $qb->orderBy('q.dateCreation', 'DESC'),
            };

            $quizzes = $this->paginator->paginate($qb->getQuery(), $request->query->getInt('page', 1), 10);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la récupération des quiz : ' . $e->getMessage());
            $quizzes = [];
        }

        $coursList = $coursRepository->findAll();
        $coursMap  = [];
        foreach ($coursList as $c) {
            $coursMap[$c->getId()] = $c->getTitre();
        }

        return $this->render('enseignant/quiz/index.html.twig', [
            'quizzes'       => $quizzes,
            'coursMap'      => $coursMap,
            'search'        => $search,
            'enseignant'    => $enseignant,
            'current_user'  => $enseignant,
            'is_etudiant'   => false,
            'is_admin'      => false,
            'is_enseignant' => true,
        ]);
    }

    #[Route('/{id<\d+>}/modifier', name: 'quiz_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, AuthChecker $authChecker, CoursRepository $coursRepository): Response
    {
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour modifier un quiz.');
            return $this->redirectToRoute('app_login');
        }
        if (!$authChecker->isEnseignant()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $authChecker->getCurrentUser();
        $quiz = $this->quizRepository->find($id);

        if (!$quiz || $quiz->getIdCreateur() !== $enseignant->getId()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à modifier ce quiz.');
            return $this->redirectToRoute('teacher_quiz_index');
        }

        $coursList = $coursRepository->findAll();
        $coursChoices = [];
        foreach ($coursList as $c) {
            $coursChoices[$c->getTitre()] = $c->getId();
        }

        $form = $this->createForm(QuizType::class, $quiz, [
            'cours_choices' => $coursChoices,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->flush();
                $this->addFlash('success', 'Le quiz a été modifié avec succès !');
                return $this->redirectToRoute('teacher_quiz_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
            }
        }

        return $this->render('enseignant/quiz/edit.html.twig', [
            'quiz'          => $quiz,
            'form'          => $form,
            'enseignant'    => $enseignant,
            'current_user'  => $enseignant,
            'is_etudiant'   => false,
            'is_admin'      => false,
            'is_enseignant' => true,
        ]);
    }

    #[Route('/{id<\d+>}/supprimer', name: 'quiz_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, AuthChecker $authChecker): Response
    {
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour supprimer un quiz.');
            return $this->redirectToRoute('app_login');
        }
        if (!$authChecker->isEnseignant()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $authChecker->getCurrentUser();
        $quiz = $this->quizRepository->find($id);

        if (!$quiz || $quiz->getIdCreateur() !== $enseignant->getId()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer ce quiz.');
            return $this->redirectToRoute('teacher_quiz_index');
        }

        if (!$this->isCsrfTokenValid('delete' . $quiz->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('teacher_quiz_index');
        }

        try {
            $this->quizService->deleteQuiz($quiz);
            $this->addFlash('success', 'Le quiz a été supprimé avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }

        return $this->redirectToRoute('teacher_quiz_index');
    }

    #[Route('/{id<\d+>}/dupliquer', name: 'quiz_duplicate', methods: ['POST'])]
    public function duplicate(Request $request, int $id, AuthChecker $authChecker): Response
    {
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour dupliquer un quiz.');
            return $this->redirectToRoute('app_login');
        }
        if (!$authChecker->isEnseignant()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $authChecker->getCurrentUser();
        $quiz = $this->quizRepository->find($id);

        if (!$quiz || $quiz->getIdCreateur() !== $enseignant->getId()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à dupliquer ce quiz.');
            return $this->redirectToRoute('teacher_quiz_index');
        }

        if (!$this->isCsrfTokenValid('duplicate' . $quiz->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('teacher_quiz_index');
        }

        try {
            $this->quizService->duplicateQuiz($quiz, $enseignant->getId());
            $this->addFlash('success', 'Le quiz a été dupliqué avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la duplication : ' . $e->getMessage());
        }

        return $this->redirectToRoute('teacher_quiz_index');
    }

    #[Route('/{id<\d+>}/questions', name: 'quiz_manage_questions', methods: ['GET'])]
    public function manageQuestions(int $id, AuthChecker $authChecker): Response
    {
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour gérer les questions.');
            return $this->redirectToRoute('app_login');
        }
        if (!$authChecker->isEnseignant()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $authChecker->getCurrentUser();
        $quiz = $this->quizRepository->find($id);

        if (!$quiz || $quiz->getIdCreateur() !== $enseignant->getId()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à gérer les questions de ce quiz.');
            return $this->redirectToRoute('teacher_quiz_index');
        }

        return $this->render('enseignant/quiz/manage_questions.html.twig', [
            'quiz'          => $quiz,
            'enseignant'    => $enseignant,
            'current_user'  => $enseignant,
            'is_etudiant'   => false,
            'is_admin'      => false,
            'is_enseignant' => true,
        ]);
    }

    #[Route('/{id<\d+>}/resultats', name: 'quiz_teacher_resultats', methods: ['GET'])]
    public function teacherResultats(int $id, AuthChecker $authChecker, EtudiantRepository $etudiantRepository): Response
    {
        if (!$authChecker->isLoggedIn()) { return $this->redirectToRoute('app_login'); }
        if (!$authChecker->isEnseignant()) { return $this->redirectToRoute('app_home'); }

        $enseignant = $authChecker->getCurrentUser();
        $quiz = $this->quizRepository->find($id);

        if (!$quiz || $quiz->getIdCreateur() !== $enseignant->getId()) {
            $this->addFlash('error', 'Non autorisé.');
            return $this->redirectToRoute('teacher_quiz_index');
        }

        $resultats = $this->resultatQuizRepository->findAllByQuiz($id);

        $etudiants = [];
        foreach ($resultats as $resultat) {
            $etudiantId = $resultat->getIdEtudiant();
            if (!isset($etudiants[$etudiantId])) {
                $etudiants[$etudiantId] = $etudiantRepository->find($etudiantId);
            }
        }

        return $this->render('enseignant/quiz/resultats_etudiants.html.twig', [
            'quiz'          => $quiz,
            'resultats'     => $resultats,
            'etudiants'     => $etudiants,
            'enseignant'    => $enseignant,
            'current_user'  => $enseignant,
            'is_etudiant'   => false,
            'is_admin'      => false,
            'is_enseignant' => true,
        ]);
    }

    // ===========================
    // STUDENT ROUTES
    // ===========================

    #[Route('/test', name: 'quiz_test', methods: ['GET'])]
    public function test(): Response
    {
        return $this->render('etudiant/quiz/test.html.twig');
    }

    #[Route('/', name: 'quiz_index', methods: ['GET'])]
    public function index(Request $request, AuthChecker $authChecker, CoursRepository $coursRepository): Response
    {
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour accéder aux quiz.');
            return $this->redirectToRoute('app_login');
        }
        if (!$authChecker->isEtudiant()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux étudiants.');
            return $this->redirectToRoute('app_home');
        }

        $user = $authChecker->getCurrentUser();
        if (!$user instanceof Etudiant) {
            $this->addFlash('error', 'Type d\'utilisateur incorrect.');
            return $this->redirectToRoute('app_home');
        }

        $student    = $user;
        $search     = $request->query->get('search', '');
        $type       = $request->query->get('type', '');
        $difficulte = $request->query->get('difficulte', '');
        $sort       = $request->query->get('sort', 'recent');

        if (!in_array($sort, ['recent', 'titre', 'titre_desc', 'difficulte_asc', 'difficulte_desc'])) {
            $sort = 'recent';
        }

        $historiqueAutoeval = $this->quizRepository->findBy(
            ['idCreateur' => $student->getId(), 'typeQuiz' => 'autoevaluation'],
            ['dateCreation' => 'DESC']
        );

        try {
            $accessData        = $this->buildStudentQuizAccessData($student);
            $enrolledCourseIds = $accessData['course_ids'];
            $linkedQuizIds     = $accessData['linked_quiz_ids'];

            if ($enrolledCourseIds === [] && $linkedQuizIds === []) {
                return $this->render('etudiant/quiz/index.html.twig', [
                    'quizzes'            => [],
                    'coursMap'           => [],
                    'search'             => $search,
                    'student'            => $student,
                    'current_user'       => $student,
                    'is_etudiant'        => true,
                    'is_admin'           => false,
                    'is_enseignant'      => false,
                    'historiqueAutoeval' => $historiqueAutoeval,
                ]);
            }

            $qb  = $this->quizRepository->createQueryBuilder('q');
            $now = new \DateTime();

            if ($enrolledCourseIds !== [] && $linkedQuizIds !== []) {
                $qb->andWhere('q.idCours IN (:courseIds) OR q.id IN (:linkedQuizIds)')
                   ->setParameter('courseIds', $enrolledCourseIds)
                   ->setParameter('linkedQuizIds', $linkedQuizIds);
            } elseif ($enrolledCourseIds !== []) {
                $qb->andWhere('q.idCours IN (:courseIds)')
                   ->setParameter('courseIds', $enrolledCourseIds);
            } else {
                $qb->andWhere('q.id IN (:linkedQuizIds)')
                   ->setParameter('linkedQuizIds', $linkedQuizIds);
            }

            $qb->andWhere('q.dateDebutDisponibilite IS NULL OR q.dateDebutDisponibilite <= :now')
               ->andWhere('q.dateFinDisponibilite IS NULL OR q.dateFinDisponibilite >= :now')
               ->andWhere('q.typeQuiz != :autoeval OR q.typeQuiz IS NULL') // ✅ exclure les auto-évaluations
               ->setParameter('now', $now)
               ->setParameter('autoeval', 'autoevaluation');

            if (!empty($search)) {
                $s = trim(substr($search, 0, 100));
                $qb->andWhere($qb->expr()->orX(
                    'q.titre LIKE :search',
                    'q.description LIKE :search',
                    'q.typeQuiz LIKE :search',
                    'q.instructions LIKE :search'
                ))->setParameter('search', '%' . $s . '%');
            }

            if (!empty($type) && in_array($type, ['evaluation', 'entrainement', 'revision', 'diagnostique'])) {
                $qb->andWhere('q.typeQuiz = :type')->setParameter('type', $type);
            }

            if (!empty($difficulte)) {
                match ($difficulte) {
                    'facile'    => $qb->andWhere('q.difficulteMoyenne >= 1 AND q.difficulteMoyenne <= 3'),
                    'moyen'     => $qb->andWhere('q.difficulteMoyenne > 3 AND q.difficulteMoyenne <= 6'),
                    'difficile' => $qb->andWhere('q.difficulteMoyenne > 6 AND q.difficulteMoyenne <= 10'),
                    default     => null,
                };
            }

            match ($sort) {
                'titre'           => $qb->orderBy('q.titre', 'ASC'),
                'titre_desc'      => $qb->orderBy('q.titre', 'DESC'),
                'difficulte_asc'  => $qb->orderBy('q.difficulteMoyenne', 'ASC')->addOrderBy('q.titre', 'ASC'),
                'difficulte_desc' => $qb->orderBy('q.difficulteMoyenne', 'DESC')->addOrderBy('q.titre', 'ASC'),
                default           => $qb->orderBy('q.dateCreation', 'DESC'),
            };

            $quizzes = $this->paginator->paginate($qb->getQuery(), $request->query->getInt('page', 1), 9);

            $coursList = $coursRepository->findBy(['id' => $enrolledCourseIds]);
            $coursMap  = [];
            foreach ($coursList as $c) {
                $coursMap[$c->getId()] = $c->getTitre();
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la récupération des quiz : ' . $e->getMessage());
            $quizzes  = [];
            $coursMap = [];
        }

        return $this->render('etudiant/quiz/index.html.twig', [
            'quizzes'            => $quizzes,
            'coursMap'           => $coursMap,
            'search'             => $search,
            'student'            => $student,
            'current_user'       => $student,
            'is_etudiant'        => true,
            'is_admin'           => false,
            'is_enseignant'      => false,
            'historiqueAutoeval' => $historiqueAutoeval,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'quiz_show', methods: ['GET'])]
    public function show(int $id, AuthChecker $authChecker, CoursRepository $coursRepository): Response
    {
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour voir ce quiz.');
            return $this->redirectToRoute('app_login');
        }
        if (!$authChecker->isEtudiant()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }

        $student = $authChecker->getCurrentUser();
        if (!$student instanceof Etudiant) {
            $this->addFlash('error', 'Type d\'utilisateur incorrect.');
            return $this->redirectToRoute('app_home');
        }

        $quiz = $this->quizRepository->find($id);
        if (!$quiz) {
            $this->addFlash('error', 'Quiz non trouvé.');
            return $this->redirectToRoute('quiz_index');
        }

        if (!$this->studentCanAccessQuiz($student, $quiz)) {
            $this->addFlash('error', 'Ce quiz ne fait pas partie de vos cours inscrits.');
            return $this->redirectToRoute('quiz_index');
        }

        $now = new \DateTime();
        if ($quiz->getDateDebutDisponibilite() && $quiz->getDateDebutDisponibilite() > $now) {
            $this->addFlash('warning', 'Ce quiz n\'est pas encore disponible.');
            return $this->redirectToRoute('quiz_index');
        }
        if ($quiz->getDateFinDisponibilite() && $quiz->getDateFinDisponibilite() < $now) {
            $this->addFlash('warning', 'Ce quiz n\'est plus disponible.');
            return $this->redirectToRoute('quiz_index');
        }

        try {
            $stats         = $this->quizService->getQuizStatistics($id);
            $historique    = $this->resultatQuizRepository->findByEtudiantAndQuiz($id, $student->getId());
            $nbTentatives  = count($historique);
            $maxTentatives = $quiz->getNombreTentativesAutorisees();
            $peutPasser    = $maxTentatives === null || $nbTentatives < $maxTentatives;

            $coursMap = [];
            if ($quiz->getIdCours()) {
                $cours = $coursRepository->find($quiz->getIdCours());
                if ($cours) {
                    $coursMap[$cours->getId()] = $cours->getTitre();
                }
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du chargement du quiz : ' . $e->getMessage());
            return $this->redirectToRoute('quiz_index');
        }

        return $this->render('etudiant/quiz/show.html.twig', [
            'quiz'          => $quiz,
            'stats'         => $stats,
            'historique'    => $historique,
            'nbTentatives'  => $nbTentatives,
            'maxTentatives' => $maxTentatives,
            'peutPasser'    => $peutPasser,
            'coursMap'      => $coursMap,
            'student'       => $student,
            'current_user'  => $student,
            'is_etudiant'   => true,
            'is_admin'      => false,
            'is_enseignant' => false,
        ]);
    }

    #[Route('/{id<\d+>}/passer', name: 'quiz_show_complet', methods: ['GET'])]
    public function showComplet(int $id, AuthChecker $authChecker): Response
    {
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour passer ce quiz.');
            return $this->redirectToRoute('app_login');
        }
        if (!$authChecker->isEtudiant()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }

        $student = $authChecker->getCurrentUser();
        if (!$student instanceof Etudiant) {
            $this->addFlash('error', 'Type d\'utilisateur incorrect.');
            return $this->redirectToRoute('app_home');
        }

        // ✅ Chargement sans JOIN pour éviter les erreurs sur ordre_affichage
        $quiz = $this->quizRepository->find($id);
        if (!$quiz) {
            $this->addFlash('error', 'Quiz non trouvé.');
            return $this->redirectToRoute('quiz_index');
        }

        if (!$this->studentCanAccessQuiz($student, $quiz)) {
            $this->addFlash('error', 'Ce quiz ne fait pas partie de vos cours inscrits.');
            return $this->redirectToRoute('quiz_index');
        }

        $now = new \DateTime();
        if ($quiz->getDateDebutDisponibilite() && $quiz->getDateDebutDisponibilite() > $now) {
            $this->addFlash('warning', 'Ce quiz n\'est pas encore disponible.');
            return $this->redirectToRoute('quiz_index');
        }
        if ($quiz->getDateFinDisponibilite() && $quiz->getDateFinDisponibilite() < $now) {
            $this->addFlash('warning', 'Ce quiz n\'est plus disponible.');
            return $this->redirectToRoute('quiz_index');
        }

        $maxTentatives = $quiz->getNombreTentativesAutorisees();
        if ($maxTentatives !== null) {
            $nbTentatives = $this->resultatQuizRepository->countTentatives($id, $student->getId());
            if ($nbTentatives >= $maxTentatives) {
                $this->addFlash('error', sprintf(
                    'Vous avez atteint le nombre maximum de tentatives (%d) pour ce quiz.',
                    $maxTentatives
                ));
                return $this->redirectToRoute('quiz_show', ['id' => $id]);
            }
        }

        if ($quiz->getQuestions()->count() === 0) {
            $this->addFlash('warning', 'Ce quiz ne contient aucune question.');
            return $this->redirectToRoute('quiz_index');
        }

        return $this->render('etudiant/quiz/take.html.twig', [
            'quiz'          => $quiz,
            'student'       => $student,
            'current_user'  => $student,
            'is_etudiant'   => true,
            'is_admin'      => false,
            'is_enseignant' => false,
        ]);
    }

    #[Route('/{id<\d+>}/soumettre', name: 'quiz_submit', methods: ['POST'])]
    public function submit(Request $request, int $id, AuthChecker $authChecker): Response
    {
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour soumettre un quiz.');
            return $this->redirectToRoute('app_login');
        }
        if (!$authChecker->isEtudiant()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }

        $student = $authChecker->getCurrentUser();
        if (!$student instanceof Etudiant) {
            $this->addFlash('error', 'Type utilisateur incorrect.');
            return $this->redirectToRoute('app_home');
        }

        try {
            $quiz = $this->quizRepository->find($id);
            if (!$quiz) {
                $this->addFlash('error', 'Quiz non trouvé.');
                return $this->redirectToRoute('quiz_index');
            }

            if (!$this->studentCanAccessQuiz($student, $quiz)) {
                $this->addFlash('error', 'Ce quiz ne fait pas partie de vos cours inscrits.');
                return $this->redirectToRoute('quiz_index');
            }

            $studentAnswers = $request->request->all('answers');
            if (empty($studentAnswers)) {
                $this->addFlash('warning', 'Aucune réponse n\'a été soumise.');
                return $this->redirectToRoute('quiz_show_complet', ['id' => $id]);
            }

            foreach ($studentAnswers as $questionId => $answerId) {
                if (!is_numeric($questionId) || !is_numeric($answerId)) {
                    $this->addFlash('error', 'Données invalides soumises.');
                    return $this->redirectToRoute('quiz_show_complet', ['id' => $id]);
                }
            }

            $results      = [];
            $totalPoints  = 0;
            $earnedPoints = 0;

            foreach ($quiz->getQuestions() as $question) {
                $questionId      = $question->getId();
                $studentAnswerId = $studentAnswers[$questionId] ?? null;

                $correctAnswer = null;
                $studentAnswer = null;
                $isCorrect     = false;

                foreach ($question->getReponses() as $reponse) {
                    if ($reponse->getEstCorrecte()) {
                        $correctAnswer = $reponse;
                    }
                    if ($reponse->getId() == $studentAnswerId) {
                        $studentAnswer = $reponse;
                    }
                }

                $questionPoints = $question->getPoints() ?? 1;
                $totalPoints   += $questionPoints;

                if ($studentAnswer && $studentAnswer->getEstCorrecte()) {
                    $isCorrect     = true;
                    $earnedPoints += $questionPoints;
                }

                $results[] = [
                    'question'      => $question,
                    'studentAnswer' => $studentAnswer,
                    'correctAnswer' => $correctAnswer,
                    'isCorrect'     => $isCorrect,
                    'points'        => $questionPoints,
                ];
            }

            $score = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;

            $sessionResults = [];
            foreach ($results as $r) {
                $sessionResults[] = [
                    'questionTexte' => $r['question']->getTexte(),
                    'studentAnswer' => $r['studentAnswer'] ? $r['studentAnswer']->getTexteReponse() : 'Sans réponse',
                    'correctAnswer' => $r['correctAnswer'] ? $r['correctAnswer']->getTexteReponse() : 'N/A',
                    'isCorrect'     => $r['isCorrect'],
                    'points'        => $r['points'],
                ];
            }

            $resultat = new ResultatQuiz();
            $resultat->setQuiz($quiz);
            $resultat->setIdEtudiant($student->getId());
            $resultat->setDatePassation(new \DateTime());
            $resultat->setScore(round($score, 2));
            $resultat->setTotalPoints($totalPoints);
            $resultat->setEarnedPoints($earnedPoints);
            $resultat->setReponsesEtudiant($sessionResults);
            $this->entityManager->persist($resultat);
            $this->entityManager->flush();

            try {
                $enseignant = $this->entityManager
                    ->getRepository(Enseignant::class)
                    ->find($quiz->getIdCreateur());

                if ($enseignant) {
                    $this->notificationService->notifierEnseignantQuizPasse(
                        enseignantUsername: $enseignant->getUsername(),
                        etudiantUsername:   $student->getUsername(),
                        quizTitre:          $quiz->getTitre(),
                        quizId:             $quiz->getId(),
                        score:              $earnedPoints,
                        scoreMax:           $totalPoints
                    );
                }
            } catch (\Exception) {}

            $request->getSession()->set('last_quiz_results', [
                'quizId'       => $quiz->getId(),
                'quizTitre'    => $quiz->getTitre(),
                'score'        => round($score, 2),
                'earnedPoints' => $earnedPoints,
                'totalPoints'  => $totalPoints,
                'results'      => $sessionResults,
                'date'         => (new \DateTime())->format('d/m/Y à H:i'),
            ]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la soumission du quiz : ' . $e->getMessage());
            return $this->redirectToRoute('quiz_show_complet', ['id' => $id]);
        }

        return $this->render('etudiant/quiz/results.html.twig', [
            'quiz'          => $quiz,
            'results'       => $results,
            'totalPoints'   => $totalPoints,
            'earnedPoints'  => $earnedPoints,
            'score'         => round($score, 2),
            'student'       => $student,
            'current_user'  => $student,
            'is_etudiant'   => true,
            'is_admin'      => false,
            'is_enseignant' => false,
        ]);
    }

    // ===========================
    // PRIVATE HELPERS
    // ===========================

    private function buildStudentQuizAccessData(Etudiant $student): array
    {
        $enrolledCourses = $student->getCoursInscrits()->toArray();
        $courseIds = array_values(array_unique(array_map(
            static fn ($course): int => (int) $course->getId(),
            $enrolledCourses
        )));

        $linkedQuizIds = [];
        foreach ($enrolledCourses as $course) {
            foreach ($course->getContenus() as $contenu) {
                if (!$contenu->isEstPublic()) {
                    continue;
                }
                $resources = $contenu->getRessourcesForDisplay();
                $quizId    = $resources['quiz_id'] ?? null;
                if (is_numeric($quizId) && (int) $quizId > 0) {
                    $linkedQuizIds[] = (int) $quizId;
                }
            }
        }

        return [
            'course_ids'      => $courseIds,
            'linked_quiz_ids' => array_values(array_unique($linkedQuizIds)),
        ];
    }

    private function studentCanAccessQuiz(Etudiant $student, Quiz $quiz): bool
    {
        // ✅ Quiz auto-évaluation créé par l'étudiant → toujours accessible
        if ($quiz->getTypeQuiz() === 'autoevaluation' && $quiz->getIdCreateur() === $student->getId()) {
            return true;
        }

        $accessData = $this->buildStudentQuizAccessData($student);
        $quizId     = (int) ($quiz->getId() ?? 0);
        $courseId   = $quiz->getIdCours();

        if ($quizId > 0 && in_array($quizId, $accessData['linked_quiz_ids'], true)) {
            return true;
        }

        return $courseId !== null && in_array((int) $courseId, $accessData['course_ids'], true);
    }
}