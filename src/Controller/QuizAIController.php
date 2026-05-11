<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\Reponse;
use App\Repository\CoursRepository;
use App\Repository\QuizRepository;
use App\Service\QuizGeneratorService;
use App\Service\AuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/quiz/ai')]
class QuizAIController extends AbstractController
{
    public function __construct(
        private QuizGeneratorService $generator,
        private EntityManagerInterface $entityManager,
        private AuthChecker $authChecker,
        private CoursRepository $coursRepository,
        private QuizRepository $quizRepository
    ) {}

    #[Route('/generate', name: 'quiz_ai_generate', methods: ['GET', 'POST'])]
    public function generate(Request $request): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user    = $this->authChecker->getCurrentUser();
        $isProf  = $this->authChecker->isEnseignant();
        $quiz    = null;
        $error   = null;
        $coursId = null;

        if ($request->isMethod('POST')) {
            try {
                $mode        = $request->request->get('mode');
                $nbQuestions = (int) $request->request->get('nb_questions', 5);

                if ($mode === 'subject') {
                    $quiz = $this->generator->generateFromSubject(
                        $request->request->get('subject'),
                        $nbQuestions,
                        $request->request->get('level', 'intermediaire')
                    );
                } elseif ($mode === 'document') {
                    $file = $request->files->get('document');
                    if ($file) {
                        $quiz = $this->generator->generateFromDocument(
                            file_get_contents($file->getPathname()),
                            $nbQuestions
                        );
                    }
                } elseif ($mode === 'cours') {
                    $coursId = (int) $request->request->get('cours_id');
                    if ($coursId > 0) {
                        $quiz = $this->generator->generateFromCours($coursId, $nbQuestions);
                    } else {
                        $error = 'Veuillez selectionner un cours.';
                    }
                }

            } catch (\Exception $e) {
                $error = 'Erreur lors de la generation : ' . $e->getMessage();
            }
        }

        $historiqueAutoeval = [];
        if (!$isProf) {
            $historiqueAutoeval = $this->quizRepository->findBy(
                ['idCreateur' => $user->getId(), 'typeQuiz' => 'autoevaluation'],
                ['dateCreation' => 'DESC']
            );
        }

        $template = $isProf
            ? 'enseignant/quiz/ai_generate.html.twig'
            : 'etudiant/quiz/ai_generate.html.twig';

        return $this->render($template, [
            'quiz'               => $quiz,
            'isProf'             => $isProf,
            'error'              => $error,
            'current_user'       => $user,
            'enseignant'         => $user,
            'student'            => $user,
            'is_etudiant'        => !$isProf,
            'is_enseignant'      => $isProf,
            'is_admin'           => false,
            'coursList'          => $this->coursRepository->findAll(),
            'historiqueAutoeval' => $historiqueAutoeval,
            'saveUrl'            => $this->generateUrl('quiz_ai_save'),
            'selectedCoursId'    => $coursId, // ✅ transmis au template pour le JS
        ]);
    }

    #[Route('/save', name: 'quiz_ai_save', methods: ['POST'])]
    public function save(Request $request): JsonResponse
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->json(['error' => 'Non autorise'], 401);
        }

        $user   = $this->authChecker->getCurrentUser();
        $isProf = $this->authChecker->isEnseignant();

        $data = json_decode($request->getContent(), true);
        if (!$data || empty($data['title']) || empty($data['questions'])) {
            return $this->json(['error' => 'Donnees invalides'], 400);
        }

        $type    = $isProf ? 'entrainement' : 'autoevaluation';

        // ✅ Récupérer le cours_id depuis le payload JSON
        $coursId = isset($data['cours_id']) && is_numeric($data['cours_id']) ? (int) $data['cours_id'] : null;

        $savedQuiz = $this->saveQuizToDatabase($data, $user->getId(), $type, $coursId);

        return $this->json([
            'success' => true,
            'quizId'  => $savedQuiz->getId(),
            'message' => $isProf
                ? 'Quiz publie et disponible pour vos etudiants !'
                : 'Quiz sauvegarde dans vos auto-evaluations !',
            'editUrl' => $isProf
                ? $this->generateUrl('quiz_edit', ['id' => $savedQuiz->getId()])
                : null,
        ]);
    }

    private function saveQuizToDatabase(array $quizData, int $userId, string $type, ?int $coursId = null): Quiz
    {
        $quiz = new Quiz();
        $quiz->setTitre($quizData['title']);
        $quiz->setDescription('Quiz genere par IA (Groq)');
        $quiz->setTypeQuiz($type);
        $quiz->setIdCreateur($userId);
        $quiz->setDateCreation(new \DateTime());

        // ✅ Lier le cours si fourni
        if ($coursId !== null && $coursId > 0) {
            $quiz->setIdCours($coursId);
        }

        $this->entityManager->persist($quiz);

        foreach ($quizData['questions'] as $index => $qData) {
            $question = new Question();
            $question->setTexte($qData['question']);
            $question->setTitre('Question ' . ($index + 1));
            $question->setTypeQuestion('choix_multiple');
            $question->setPoints(1);
            $question->setOrdreAffichage($index + 1);
            $question->setDateCreation(new \DateTime());
            $question->setExplicationReponse($qData['explanation'] ?? null);
            $question->setQuiz($quiz);

            $this->entityManager->persist($question);

            foreach ($qData['options'] as $i => $optionText) {
                $letter  = $optionText[0];
                $reponse = new Reponse();
                $reponse->setTexteReponse($optionText);
                $reponse->setEstCorrecte($letter === $qData['correct']);
                $reponse->setOrdreAffichage($i + 1);
                $reponse->setQuestion($question);

                $this->entityManager->persist($reponse);
            }
        }

        $this->entityManager->flush();

        return $quiz;
    }
}
