<?php

namespace App\Service;

use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\Reponse;
use App\Repository\QuizRepository;
use App\Repository\QuestionRepository;
use App\Repository\ReponseRepository;
use Doctrine\ORM\EntityManagerInterface;

class QuizService
{
    private EntityManagerInterface $entityManager;
    private QuizRepository $quizRepository;
    private QuestionRepository $questionRepository;
    private ReponseRepository $reponseRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        QuizRepository $quizRepository,
        QuestionRepository $questionRepository,
        ReponseRepository $reponseRepository
    ) {
        $this->entityManager = $entityManager;
        $this->quizRepository = $quizRepository;
        $this->questionRepository = $questionRepository;
        $this->reponseRepository = $reponseRepository;
    }

    /**
     * Créer un quiz complet avec questions et réponses
     */
    public function createQuizComplet(array $data): Quiz
    {
        $this->entityManager->beginTransaction();

        try {
            $quiz = new Quiz();
            $quiz->setTitre($data['titre'] ?? null);
            $quiz->setDescription($data['description'] ?? null);
            $quiz->setIdCreateur($data['id_createur']);
            $quiz->setIdCours($data['id_cours']);
            $quiz->setTypeQuiz($data['type_quiz'] ?? null);
            $quiz->setDateCreation(new \DateTime());

            if (isset($data['date_debut_disponibilite'])) {
                $quiz->setDateDebutDisponibilite(new \DateTime($data['date_debut_disponibilite']));
            }
            if (isset($data['date_fin_disponibilite'])) {
                $quiz->setDateFinDisponibilite(new \DateTime($data['date_fin_disponibilite']));
            }

            $quiz->setDureeMinutes($data['duree_minutes'] ?? null);
            $quiz->setNombreTentativesAutorisees($data['nombre_tentatives_autorisees'] ?? null);
            $quiz->setDifficulteMoyenne($data['difficulte_moyenne'] ?? null);
            $quiz->setInstructions($data['instructions'] ?? null);
            $quiz->setAfficherCorrectionApres($data['afficher_correction_apres'] ?? null);

            $this->entityManager->persist($quiz);
            $this->entityManager->flush();

            if (isset($data['questions']) && is_array($data['questions'])) {
                foreach ($data['questions'] as $questionData) {
                    $this->addQuestionToQuiz($quiz, $questionData);
                }
            }

            $this->entityManager->commit();
            return $quiz;

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    public function addQuestionToQuiz(Quiz $quiz, array $questionData): Question
    {
        $question = new Question();
        $question->setQuiz($quiz);
        $question->setEnonce($questionData['enonce'] ?? null);
        $question->setTypeQuiz($questionData['type_quiz'] ?? null);
        $question->setPoints($questionData['points'] ?? null);

        $ordre = $questionData['ordre_affichage'] ?? $this->questionRepository->getNextOrdreAffichage($quiz->getId());
        $question->setOrdreAffichage($ordre);

        $question->setMediaUrl($questionData['media_url'] ?? null);
        $question->setFeedbackCorrect($questionData['feedback_correct'] ?? null);
        $question->setFeedbackIncorrect($questionData['feedback_incorrect'] ?? null);
        $question->setTempsSuggereSecondes($questionData['temps_suggere_secondes'] ?? null);
        $question->setCompetencesCibles($questionData['competences_cibles'] ?? null);

        $this->entityManager->persist($question);
        $this->entityManager->flush();

        if (isset($questionData['reponses']) && is_array($questionData['reponses'])) {
            foreach ($questionData['reponses'] as $reponseData) {
                $this->addReponseToQuestion($question, $reponseData);
            }
        }

        return $question;
    }

    public function addReponseToQuestion(Question $question, array $reponseData): Reponse
    {
        $reponse = new Reponse();
        $reponse->setQuestion($question);
        $reponse->setTexteReponse($reponseData['texte_reponse'] ?? null);
        $reponse->setEstCorrecte($reponseData['est_correcte'] ?? false);

        $ordre = $reponseData['ordre_affichage'] ?? $this->reponseRepository->getNextOrdreAffichage($question->getId());
        $reponse->setOrdreAffichage($ordre);

        $reponse->setPourcentagePoints($reponseData['pourcentage_points'] ?? null);
        $reponse->setFeedbackSpecifique($reponseData['feedback_specifique'] ?? null);
        $reponse->setMediaUrl($reponseData['media_url'] ?? null);

        $this->entityManager->persist($reponse);
        $this->entityManager->flush();

        return $reponse;
    }

    public function updateQuiz(Quiz $quiz, array $data): Quiz
    {
        if (isset($data['titre'])) $quiz->setTitre($data['titre']);
        if (isset($data['description'])) $quiz->setDescription($data['description']);
        if (isset($data['type_quiz'])) $quiz->setTypeQuiz($data['type_quiz']);
        if (isset($data['date_debut_disponibilite'])) $quiz->setDateDebutDisponibilite(new \DateTime($data['date_debut_disponibilite']));
        if (isset($data['date_fin_disponibilite'])) $quiz->setDateFinDisponibilite(new \DateTime($data['date_fin_disponibilite']));
        if (isset($data['duree_minutes'])) $quiz->setDureeMinutes($data['duree_minutes']);
        if (isset($data['nombre_tentatives_autorisees'])) $quiz->setNombreTentativesAutorisees($data['nombre_tentatives_autorisees']);
        if (isset($data['difficulte_moyenne'])) $quiz->setDifficulteMoyenne($data['difficulte_moyenne']);
        if (isset($data['instructions'])) $quiz->setInstructions($data['instructions']);
        if (isset($data['afficher_correction_apres'])) $quiz->setAfficherCorrectionApres($data['afficher_correction_apres']);

        $this->entityManager->flush();
        return $quiz;
    }

    public function deleteQuiz(Quiz $quiz): void
    {
        $this->entityManager->beginTransaction();

        try {
            $questions = $this->questionRepository->findByQuiz($quiz->getId());

            foreach ($questions as $question) {
                $this->deleteQuestion($question);
            }

            $this->entityManager->remove($quiz);
            $this->entityManager->flush();

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    public function deleteQuestion(Question $question): void
    {
        $reponses = $this->reponseRepository->findByQuestion($question->getId());

        foreach ($reponses as $reponse) {
            $this->entityManager->remove($reponse);
        }

        $this->entityManager->remove($question);
        $this->entityManager->flush();
    }

    public function duplicateQuiz(Quiz $originalQuiz, int $newIdCreateur): Quiz
    {
        $this->entityManager->beginTransaction();

        try {
            $newQuiz = new Quiz();
            $newQuiz->setTitre($originalQuiz->getTitre() . ' (Copie)');
            $newQuiz->setDescription($originalQuiz->getDescription());
            $newQuiz->setIdCreateur($newIdCreateur);
            $newQuiz->setIdCours($originalQuiz->getIdCours());
            $newQuiz->setTypeQuiz($originalQuiz->getTypeQuiz());
            $newQuiz->setDateCreation(new \DateTime());
            $newQuiz->setDureeMinutes($originalQuiz->getDureeMinutes());
            $newQuiz->setNombreTentativesAutorisees($originalQuiz->getNombreTentativesAutorisees());
            $newQuiz->setDifficulteMoyenne($originalQuiz->getDifficulteMoyenne());
            $newQuiz->setInstructions($originalQuiz->getInstructions());
            $newQuiz->setAfficherCorrectionApres($originalQuiz->getAfficherCorrectionApres());

            $this->entityManager->persist($newQuiz);
            $this->entityManager->flush();

            $questions = $this->questionRepository->findByQuiz($originalQuiz->getId());
            foreach ($questions as $originalQuestion) {
                $this->duplicateQuestion($originalQuestion, $newQuiz);
            }

            $this->entityManager->commit();
            return $newQuiz;

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    private function duplicateQuestion(Question $originalQuestion, Quiz $newQuiz): Question
    {
        $newQuestion = new Question();
        $newQuestion->setQuiz($newQuiz);
        $newQuestion->setEnonce($originalQuestion->getEnonce());
        $newQuestion->setTypeQuiz($originalQuestion->getTypeQuiz());
        $newQuestion->setPoints($originalQuestion->getPoints());
        $newQuestion->setOrdreAffichage($originalQuestion->getOrdreAffichage());
        $newQuestion->setMediaUrl($originalQuestion->getMediaUrl());
        $newQuestion->setFeedbackCorrect($originalQuestion->getFeedbackCorrect());
        $newQuestion->setFeedbackIncorrect($originalQuestion->getFeedbackIncorrect());
        $newQuestion->setTempsSuggereSecondes($originalQuestion->getTempsSuggereSecondes());
        $newQuestion->setCompetencesCibles($originalQuestion->getCompetencesCibles());

        $this->entityManager->persist($newQuestion);
        $this->entityManager->flush();

        $reponses = $this->reponseRepository->findByQuestion($originalQuestion->getId());
        foreach ($reponses as $originalReponse) {
            $this->duplicateReponse($originalReponse, $newQuestion);
        }

        return $newQuestion;
    }

    private function duplicateReponse(Reponse $originalReponse, Question $newQuestion): Reponse
    {
        $newReponse = new Reponse();
        $newReponse->setQuestion($newQuestion);
        $newReponse->setTexteReponse($originalReponse->getTexteReponse());
        $newReponse->setEstCorrecte($originalReponse->getEstCorrecte());
        $newReponse->setOrdreAffichage($originalReponse->getOrdreAffichage());
        $newReponse->setPourcentagePoints($originalReponse->getPourcentagePoints());
        $newReponse->setFeedbackSpecifique($originalReponse->getFeedbackSpecifique());
        $newReponse->setMediaUrl($originalReponse->getMediaUrl());

        $this->entityManager->persist($newReponse);
        $this->entityManager->flush();

        return $newReponse;
    }

   public function getQuizStatistics(int $idQuiz): array
{
    $quiz = $this->quizRepository->find($idQuiz);
    
    if (!$quiz) {
        return [];
    }

    $questions = $this->questionRepository->findByQuiz($idQuiz);
    $totalQuestions = count($questions);

    $questionsByTypeRaw = $this->questionRepository->countByTypeQuiz($idQuiz);
    $questionsByType = [];
    foreach ($questionsByTypeRaw as $q) {
        $questionsByType[$q['type_quiz']] = (int)$q['nb'];
    }

    $totalPoints = $this->questionRepository->getTotalPoints($idQuiz);

    return [
        'totalQuestions' => $totalQuestions,  // ← Changé de total_questions
        'questionsByType' => $questionsByType, // ← Changé de questions_by_type
        'totalPoints' => $totalPoints,         // ← Changé de total_points
        'averageScore' => 0,                   // ← Ajouté
        'totalAttempts' => 0,                  // ← Ajouté
    ];
}
}
