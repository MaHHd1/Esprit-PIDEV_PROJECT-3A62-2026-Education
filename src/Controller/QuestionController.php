<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Question;
use App\Entity\Reponse;
use App\Form\QuestionType;
use App\Repository\QuestionRepository;
use App\Repository\QuizRepository;
use App\Service\QuizService;
use App\Service\AuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/question')]
class QuestionController extends AbstractController
{
    private QuizService $quizService;
    private QuestionRepository $questionRepository;
    private QuizRepository $quizRepository;
    private EntityManagerInterface $entityManager;
    private AuthChecker $authChecker;

    public function __construct(
        QuizService $quizService,
        QuestionRepository $questionRepository,
        QuizRepository $quizRepository,
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ) {
        $this->quizService = $quizService;
        $this->questionRepository = $questionRepository;
        $this->quizRepository = $quizRepository;
        $this->entityManager = $entityManager;
        $this->authChecker = $authChecker;
    }

    // ===========================
    // TEACHER ROUTES
    // ===========================

#[Route('/quiz/{idQuiz}/nouvelle', name: 'teacher_question_new', methods: ['GET', 'POST'])]
public function new(Request $request, int $idQuiz, AuthChecker $authChecker): Response
{
    // ========== AUTHENTICATION & AUTHORIZATION ==========
    if (!$authChecker->isLoggedIn()) {
        $this->addFlash('error', 'Veuillez vous connecter pour créer une question.');
        return $this->redirectToRoute('app_login');
    }
    
    if (!$authChecker->isEnseignant()) {
        $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
        return $this->redirectToRoute('app_home');
    }
    
    $enseignant = $authChecker->getCurrentUser();
    
    $quiz = $this->quizRepository->find($idQuiz);
    if (!$quiz) {
        $this->addFlash('error', 'Quiz non trouvé');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    // Vérifier que l'enseignant est le créateur du quiz
    if ($quiz->getIdCreateur() !== $enseignant->getId()) {
        $this->addFlash('error', 'Vous n\'êtes pas autorisé à ajouter des questions à ce quiz');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    $question = new Question();
    $question->setQuiz($quiz);
    $question->setCreateur($enseignant);
    $question->setDateCreation(new \DateTime());

    // Créer le formulaire
    $form = $this->createForm(QuestionType::class, $question, [
        'allow_extra_fields' => true,
    ]);
    
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        // Récupérer toutes les données
        $allData = $request->request->all();
        
        // DEBUG: Activer pour voir ce qui est envoyé
        // echo "<pre>"; print_r($allData); die();
        
        // 1. Chercher question_data
        $questionData = $request->request->get('question_data');
        if (!$questionData && isset($allData['question_data'])) {
            $questionData = $allData['question_data'];
        }
        
        if (!$questionData) {
            $this->addFlash('error', 'Les données de la question sont manquantes');
            return $this->render('enseignant/question/new.html.twig', [
                'question' => $question,
                'quiz' => $quiz,
                'form' => $form->createView(),
                'enseignant' => $enseignant,
                'current_user' => $enseignant,
                'is_etudiant' => false,
                'is_admin' => false,
                'is_enseignant' => true,
            ]);
        }
        
        $data = json_decode($questionData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !$data) {
            $this->addFlash('error', 'Format de données invalide');
            return $this->render('enseignant/question/new.html.twig', [
                'question' => $question,
                'quiz' => $quiz,
                'form' => $form->createView(),
                'enseignant' => $enseignant,
                'current_user' => $enseignant,
                'is_etudiant' => false,
                'is_admin' => false,
                'is_enseignant' => true,
            ]);
        }
        
        // 2. CRITIQUE: Gérer le mapping manuellement AVANT la validation
        $formData = $request->request->all()['question'] ?? [];
        
        // Mapper typeQuestion (form) -> type_question (entity)
        if (isset($formData['typeQuestion'])) {
            $question->setTypeQuestion($formData['typeQuestion']);
        }
        
        // Mapper explicationReponse (form) -> explication_reponse (entity)
        if (isset($formData['explicationReponse'])) {
            $question->setExplicationReponse($formData['explicationReponse']);
        }
        
        // 3. Définir les valeurs par défaut depuis $data
        if (isset($data['type'])) {
            $typeMapping = [
                'qcm' => 'choix_multiple',
                'vrai_faux' => 'vrai_faux',
                'texte_libre' => 'texte_libre'
            ];
            
            if (isset($typeMapping[$data['type']])) {
                $question->setTypeQuestion($typeMapping[$data['type']]);
            }
        }
        
        // 4. Maintenant, valider le formulaire
        if ($form->isValid()) {
            try {
                // Définir l'ordre
                $ordre = $this->questionRepository->getNextOrdreAffichage($idQuiz);
                $question->setOrdreAffichage($ordre);
                
                // Points par défaut
                if (!$question->getPoints()) {
                    $question->setPoints(1);
                }
                
                // Stocker les métadonnées
                $question->setMetadata($data);
                
                // Créer les réponses
                $hasCorrectAnswer = false;
                
                if ($data['type'] === 'qcm' && isset($data['options'])) {
                    foreach ($data['options'] as $index => $option) {
                        $reponse = new Reponse();
                        $reponse->setQuestion($question);
                        $reponse->setTexteReponse($option['text'] ?? '');
                        $reponse->setEstCorrecte($option['isCorrect'] ?? false);
                        $reponse->setOrdreAffichage($index + 1);
                        
                        if ($reponse->getEstCorrecte()) {
                            $hasCorrectAnswer = true;
                        }
                        
                        $this->entityManager->persist($reponse);
                    }
                }
                
                if ($data['type'] === 'vrai_faux' && isset($data['correct'])) {
                    $reponseVrai = new Reponse();
                    $reponseVrai->setQuestion($question);
                    $reponseVrai->setTexteReponse('Vrai');
                    $reponseVrai->setEstCorrecte($data['correct'] === 'true');
                    $reponseVrai->setOrdreAffichage(1);
                    
                    $reponseFaux = new Reponse();
                    $reponseFaux->setQuestion($question);
                    $reponseFaux->setTexteReponse('Faux');
                    $reponseFaux->setEstCorrecte($data['correct'] === 'false');
                    $reponseFaux->setOrdreAffichage(2);
                    
                    if ($reponseVrai->getEstCorrecte() || $reponseFaux->getEstCorrecte()) {
                        $hasCorrectAnswer = true;
                    }
                    
                    $this->entityManager->persist($reponseVrai);
                    $this->entityManager->persist($reponseFaux);
                }
                
                // Vérifier les réponses correctes
                if ($question->getTypeQuestion() !== 'texte_libre' && !$hasCorrectAnswer) {
                    $this->addFlash('error', 'Au moins une réponse doit être marquée comme correcte.');
                    return $this->render('enseignant/question/new.html.twig', [
                        'question' => $question,
                        'quiz' => $quiz,
                        'form' => $form->createView(),
                        'enseignant' => $enseignant,
                        'current_user' => $enseignant,
                        'is_etudiant' => false,
                        'is_admin' => false,
                        'is_enseignant' => true,
                    ]);
                }
                
                // Gérer les contraintes FK
                $connection = $this->entityManager->getConnection();
                $wasForeignKeyChecks = $connection->executeQuery('SELECT @@foreign_key_checks')->fetchOne();
                $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
                
                try {
                    $this->entityManager->persist($question);
                    $this->entityManager->flush();
                    
                    $connection->executeStatement("SET FOREIGN_KEY_CHECKS = $wasForeignKeyChecks");
                    
                    $this->addFlash('success', 'La question a été créée avec succès !');
                    return $this->redirectToRoute('quiz_manage_questions', ['id' => $idQuiz]);
                    
                } catch (\Exception $e) {
                    $connection->executeStatement("SET FOREIGN_KEY_CHECKS = $wasForeignKeyChecks");
                    throw $e;
                }
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur : ' . $e->getMessage());
            }
        } else {
            // Afficher les erreurs de validation
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }
    }

    return $this->render('enseignant/question/new.html.twig', [
        'question' => $question,
        'quiz' => $quiz,
        'form' => $form->createView(),
        'enseignant' => $enseignant,
        'current_user' => $enseignant,
        'is_etudiant' => false,
        'is_admin' => false,
        'is_enseignant' => true,
    ]);
}

    /**
     * Modifier une question (teacher)
     */
    #[Route('/{id<\d+>}/modifier', name: 'teacher_question_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, int $id, AuthChecker $authChecker): Response
{
    // ========== AUTHENTICATION & AUTHORIZATION ==========
    if (!$authChecker->isLoggedIn()) {
        $this->addFlash('error', 'Veuillez vous connecter pour modifier une question.');
        return $this->redirectToRoute('app_login');
    }
    
    if (!$authChecker->isEnseignant()) {
        $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
        return $this->redirectToRoute('app_home');
    }
    
    $enseignant = $authChecker->getCurrentUser();

    $question = $this->questionRepository->find($id);

    if (!$question) {
        $this->addFlash('error', 'Question non trouvée');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    // Vérifier que l'enseignant est le créateur du quiz
    if ($question->getQuiz()->getIdCreateur() !== $enseignant->getId()) {
        $this->addFlash('error', 'Vous n\'êtes pas autorisé à modifier cette question');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    $form = $this->createForm(QuestionType::class, $question);
    $form->handleRequest($request);

    // DEBUG: Log what's being submitted
    if ($request->isMethod('POST')) {
        error_log('=== EDIT QUESTION POST REQUEST ===');
        error_log('All POST data: ' . print_r($request->request->all(), true));
        error_log('Question data field: ' . ($request->request->get('question_data') ? 'EXISTS' : 'MISSING'));
        error_log('Form submitted: ' . ($form->isSubmitted() ? 'YES' : 'NO'));
        error_log('Form valid: ' . ($form->isValid() ? 'YES' : 'NO'));
    }

    if ($form->isSubmitted() && $form->isValid()) {
        $questionData = $request->request->get('question_data');
        
        // DEBUG
        error_log('Question data received: ' . $questionData);
        
        // ✅ CONTRÔLE 1: Vérifier que question_data existe
        if (!$questionData) {
            error_log('ERROR: question_data is missing!');
            $this->addFlash('error', 'Les données de la question sont manquantes. Veuillez réessayer.');
            return $this->render('enseignant/question/edit.html.twig', [
                'question' => $question,
                'form' => $form->createView(),
                'enseignant' => $enseignant,
                'current_user' => $enseignant,
                'is_etudiant' => false,
                'is_admin' => false,
                'is_enseignant' => true,
            ]);
        }
        
        $data = json_decode($questionData, true);
        
        // ✅ CONTRÔLE 2: Vérifier que le JSON est valide
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('ERROR: Invalid JSON - ' . json_last_error_msg());
            $this->addFlash('error', 'Format de données invalide: ' . json_last_error_msg());
            return $this->render('enseignant/question/edit.html.twig', [
                'question' => $question,
                'form' => $form->createView(),
                'enseignant' => $enseignant,
                'current_user' => $enseignant,
                'is_etudiant' => false,
                'is_admin' => false,
                'is_enseignant' => true,
            ]);
        }
        
        error_log('Decoded data: ' . print_r($data, true));
        
        // ✅ CONTRÔLE 3: Vérifier que le type est défini
        if (!isset($data['type']) || empty($data['type'])) {
            $this->addFlash('error', 'Le type de question est requis');
            return $this->render('enseignant/question/edit.html.twig', [
                'question' => $question,
                'form' => $form->createView(),
                'enseignant' => $enseignant,
                'current_user' => $enseignant,
                'is_etudiant' => false,
                'is_admin' => false,
                'is_enseignant' => true,
            ]);
        }
        
        // Map frontend type to entity type
        $typeMapping = [
            'qcm' => 'choix_multiple',
            'vrai_faux' => 'vrai_faux',
            'texte_libre' => 'texte_libre'
        ];
        
        $entityType = $typeMapping[$data['type']] ?? 'choix_multiple';
        
        // Update the question type
        $question->setTypeQuestion($entityType);
        
        // Stocker les métadonnées
        $question->setMetadata($data);
        
        try {
            // Supprimer les anciennes réponses
            foreach ($question->getReponses() as $reponse) {
                $this->entityManager->remove($reponse);
            }
            // Flush to remove old responses
            $this->entityManager->flush();
            
            // Créer les nouvelles réponses selon le type
            if ($data['type'] === 'qcm' && isset($data['options'])) {
                foreach ($data['options'] as $index => $option) {
                    $reponse = new Reponse();
                    $reponse->setQuestion($question);
                    $reponse->setTexteReponse($option['text'] ?? '');
                    $reponse->setEstCorrecte($option['isCorrect'] ?? false);
                    $reponse->setOrdreAffichage($index + 1);
                    $this->entityManager->persist($reponse);
                }
            }
            
            if ($data['type'] === 'vrai_faux' && isset($data['correct'])) {
                $reponseVrai = new Reponse();
                $reponseVrai->setQuestion($question);
                $reponseVrai->setTexteReponse('Vrai');
                $reponseVrai->setEstCorrecte($data['correct'] === 'true');
                $reponseVrai->setOrdreAffichage(1);
                $this->entityManager->persist($reponseVrai);
                
                $reponseFaux = new Reponse();
                $reponseFaux->setQuestion($question);
                $reponseFaux->setTexteReponse('Faux');
                $reponseFaux->setEstCorrecte($data['correct'] === 'false');
                $reponseFaux->setOrdreAffichage(2);
                $this->entityManager->persist($reponseFaux);
            }
            
            // For texte_libre, we don't create responses but keep metadata
            
            // Save everything
            $this->entityManager->flush();
            
            error_log('SUCCESS: Question updated in database');
            $this->addFlash('success', 'La question a été modifiée avec succès !');

            return $this->redirectToRoute('quiz_manage_questions', ['id' => $question->getQuiz()->getId()]);
            
        } catch (\Exception $e) {
            error_log('ERROR saving question: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->addFlash('error', 'Erreur lors de la sauvegarde: ' . $e->getMessage());
            
            return $this->render('enseignant/question/edit.html.twig', [
                'question' => $question,
                'form' => $form->createView(),
                'enseignant' => $enseignant,
                'current_user' => $enseignant,
                'is_etudiant' => false,
                'is_admin' => false,
                'is_enseignant' => true,
            ]);
        }
    } elseif ($form->isSubmitted()) {
        // Form has validation errors
        $errors = $form->getErrors(true, false);
        error_log('FORM VALIDATION ERRORS: ' . print_r($errors, true));
        $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
    }

    return $this->render('enseignant/question/edit.html.twig', [
        'question' => $question,
        'form' => $form->createView(),
        'enseignant' => $enseignant,
        'current_user' => $enseignant,
        'is_etudiant' => false,
        'is_admin' => false,
        'is_enseignant' => true,
    ]);
}

    /**
     * Supprimer une question (teacher)
     */
    #[Route('/{id<\d+>}/supprimer', name: 'teacher_question_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, AuthChecker $authChecker): Response
    {
        // ========== AUTHENTICATION & AUTHORIZATION ==========
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour supprimer une question.');
            return $this->redirectToRoute('app_login');
        }
        
        if (!$authChecker->isEnseignant()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
            return $this->redirectToRoute('app_home');
        }
        
        $enseignant = $authChecker->getCurrentUser();

        $question = $this->questionRepository->find($id);

        if (!$question) {
            $this->addFlash('error', 'Question non trouvée');
            return $this->redirectToRoute('teacher_quiz_index');
        }

        // Vérifier que l'enseignant est le créateur du quiz
        if ($question->getQuiz()->getIdCreateur() !== $enseignant->getId()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer cette question');
            return $this->redirectToRoute('teacher_quiz_index');
        }

        $quizId = $question->getQuiz()->getId();

        if ($this->isCsrfTokenValid('delete'.$question->getId(), $request->request->get('_token'))) {
            try {
                $this->quizService->deleteQuestion($question);
                $this->addFlash('success', 'La question a été supprimée avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Token de sécurité invalide');
        }

        return $this->redirectToRoute('quiz_manage_questions', ['id' => $quizId]);
    }

    /**
     * Gérer les réponses d'une question (teacher)
     */
    #[Route('/{id<\d+>}/reponses', name: 'teacher_question_manage_reponses', methods: ['GET'])]
    public function manageReponses(int $id, AuthChecker $authChecker): Response
    {
        // ========== AUTHENTICATION & AUTHORIZATION ==========
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }
        
        if (!$authChecker->isEnseignant()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
            return $this->redirectToRoute('app_home');
        }
        
        $enseignant = $authChecker->getCurrentUser();

        $question = $this->questionRepository->find($id);

        if (!$question) {
            $this->addFlash('error', 'Question non trouvée');
            return $this->redirectToRoute('teacher_quiz_index');
        }

        // Vérifier que l'enseignant est le créateur du quiz
        if ($question->getQuiz()->getIdCreateur() !== $enseignant->getId()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à gérer les réponses de cette question');
            return $this->redirectToRoute('teacher_quiz_index');
        }

        return $this->render('enseignant/question/manage_reponses.html.twig', [
            'question' => $question,
            'enseignant' => $enseignant,
            'current_user' => $enseignant,
            'is_etudiant' => false,
            'is_admin' => false,
            'is_enseignant' => true,
        ]);
    }

    /**
     * Réorganiser l'ordre d'une question - AJAX (teacher)
     */
    #[Route('/{id<\d+>}/reordonner', name: 'teacher_question_reorder', methods: ['POST'])]
    public function reorder(Request $request, int $id, AuthChecker $authChecker): Response
    {
        // ========== AUTHENTICATION & AUTHORIZATION ==========
        if (!$authChecker->isLoggedIn()) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }
        
        if (!$authChecker->isEnseignant()) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }
        
        $enseignant = $authChecker->getCurrentUser();

        $question = $this->questionRepository->find($id);

        if (!$question) {
            return $this->json(['error' => 'Question non trouvée'], 404);
        }

        // Vérifier que l'enseignant est le créateur du quiz
        if ($question->getQuiz()->getIdCreateur() !== $enseignant->getId()) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'JSON invalide'], 400);
        }
        
        $newOrdre = $data['ordre_affichage'] ?? null;

        if ($newOrdre === null || !is_numeric($newOrdre) || $newOrdre < 0) {
            return $this->json(['error' => 'ordre_affichage requis et doit être un nombre positif'], 400);
        }

        $question->setOrdreAffichage((int)$newOrdre);
        $this->entityManager->flush();

        return $this->json(['success' => true, 'message' => 'Ordre mis à jour']);
    }

    /**
     * Réorganiser l'ordre des questions d'un quiz - AJAX (teacher)
     */
    #[Route('/quiz/{id<\d+>}/reordonner-questions', name: 'teacher_quiz_reorder_questions', methods: ['POST'])]
    public function reorderQuestions(Request $request, int $id, AuthChecker $authChecker): Response
    {
        // ========== AUTHENTICATION & AUTHORIZATION ==========
        if (!$authChecker->isLoggedIn()) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }
        
        if (!$authChecker->isEnseignant()) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }
        
        $enseignant = $authChecker->getCurrentUser();

        $quiz = $this->quizRepository->find($id);

        if (!$quiz) {
            return $this->json(['error' => 'Quiz non trouvé'], 404);
        }

        // Vérifier que l'enseignant est le créateur du quiz
        if ($quiz->getIdCreateur() !== $enseignant->getId()) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'JSON invalide'], 400);
        }
        
        $newOrder = $data['order'] ?? [];

        if (empty($newOrder) || !is_array($newOrder)) {
            return $this->json(['error' => 'Ordre non fourni ou invalide'], 400);
        }

        foreach ($newOrder as $item) {
            if (!isset($item['id']) || !isset($item['order'])) {
                return $this->json(['error' => 'Format invalide: id et order requis'], 400);
            }
            
            $questionId = $item['id'];
            $ordre = $item['order'];

            if (!is_numeric($questionId) || !is_numeric($ordre)) {
                return $this->json(['error' => 'id et order doivent être numériques'], 400);
            }

            $question = $this->questionRepository->find($questionId);
            
            if ($question && $question->getQuiz()->getId() === $quiz->getId()) {
                $question->setOrdreAffichage((int)$ordre);
            }
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Ordre des questions mis à jour'
        ]);
    }

    // ===========================
    // STUDENT ROUTES
    // ===========================

    /**
     * Afficher une question avec ses réponses (student)
     */
    #[Route('/{id<\d+>}', name: 'student_question_show', methods: ['GET'])]
    public function show(int $id, AuthChecker $authChecker): Response
    {
        // ========== AUTHENTICATION & AUTHORIZATION ==========
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour voir cette question.');
            return $this->redirectToRoute('app_login');
        }
        
        if (!$authChecker->isEtudiant()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }
        
        $student = $authChecker->getCurrentUser();

        $question = $this->questionRepository->find($id);

        if (!$question) {
            $this->addFlash('error', 'Question non trouvée');
            return $this->redirectToRoute('quiz_index');
        }

        return $this->render('question/show.html.twig', [
            'question' => $question,
            'student' => $student,
            'current_user' => $student,
            'is_etudiant' => true,
            'is_admin' => false,
            'is_enseignant' => false,
        ]);
    }
}