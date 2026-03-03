<?php

namespace App\Controller;

use App\Entity\Reponse;
use App\Entity\Utilisateur;
use App\Form\ReponseType;
use App\Repository\ReponseRepository;
use App\Repository\QuestionRepository;
use App\Service\QuizService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reponse')]
class ReponseController extends AbstractController
{
    private QuizService $quizService;
    private ReponseRepository $reponseRepository;
    private QuestionRepository $questionRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        QuizService $quizService,
        ReponseRepository $reponseRepository,
        QuestionRepository $questionRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->quizService = $quizService;
        $this->reponseRepository = $reponseRepository;
        $this->questionRepository = $questionRepository;
        $this->entityManager = $entityManager;
    }

    // ===========================
    // TEACHER ROUTES
    // ===========================

    #[Route('/question/{idQuestion<\d+>}/nouvelle', name: 'reponse_new', methods: ['GET', 'POST'])]
    public function new(Request $request, int $idQuestion): Response
    {
        $question = $this->questionRepository->find($idQuestion);

        if (!$question) {
            $this->addFlash('error', 'Question non trouvée');
            return $this->redirectToRoute('quiz_index');
        }

        $reponsesDisponibles = $this->reponseRepository->findBy(
            ['question' => $question],
            ['ordreAffichage' => 'ASC']
        );

        if ($request->isMethod('POST')) {
            $reponseChoisieId = $request->request->get('reponse_choisie');
            $texteLibre = $request->request->get('texte_libre');

            $reponseEtudiant = new Reponse();
            $reponseEtudiant->setQuestion($question);

            if ($reponseChoisieId) {
                $reponseChoisie = $this->reponseRepository->find($reponseChoisieId);
                if ($reponseChoisie && $reponseChoisie->getQuestion()->getId() === $idQuestion) {
                    $reponseEtudiant->setTexteReponse($reponseChoisie->getTexteReponse());
                    $reponseEtudiant->setEstCorrecte($reponseChoisie->getEstCorrecte());
                }
            } elseif ($texteLibre && trim($texteLibre) !== '') {
                $reponseEtudiant->setTexteReponse(trim($texteLibre));

                $metadata = $question->getMetadata();
                $estCorrecte = false;

                if ($metadata) {
                    $metadataArray = json_decode($metadata, true);
                    $keywords = $metadataArray['keywords'] ?? [];

                    foreach ($keywords as $keyword) {
                        if (stripos($texteLibre, $keyword) !== false) {
                            $estCorrecte = true;
                            break;
                        }
                    }
                }

                $reponseEtudiant->setEstCorrecte($estCorrecte);
            }

            $this->entityManager->persist($reponseEtudiant);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre réponse a été enregistrée !');

            return $this->redirectToRoute('reponse_list', ['idQuestion' => $idQuestion]);
        }

        return $this->render('reponse/new.html.twig', [
            'question' => $question,
            'reponses_disponibles' => $reponsesDisponibles,
        ]);
    }

    #[Route('/{id<\d+>}/modifier', name: 'reponse_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Utilisateur) {
            $this->addFlash('error', 'Utilisateur non connecte');
            return $this->redirectToRoute('app_login');
        }

        $reponse = $this->reponseRepository->find($id);

        if (!$reponse) {
            $this->addFlash('error', 'Réponse non trouvée');
            return $this->redirectToRoute('quiz_index');
        }

        $quiz = $reponse->getQuestion()->getQuiz();
        $creatorId = $quiz->getIdCreateur();

        if ($creatorId !== null && $creatorId !== $currentUser->getId()) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de modifier cette réponse');
            return $this->redirectToRoute('quiz_index');
        }

        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'La réponse a été modifiée avec succès !');

            return $this->redirectToRoute('question_manage_reponses', [
                'id' => $reponse->getQuestion()->getId()
            ]);
        }

        return $this->render('reponse/edit.html.twig', [
            'reponse' => $reponse,
            'form' => $form,
        ]);
    }

    #[Route('/{id<\d+>}/supprimer', name: 'reponse_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Utilisateur) {
            $this->addFlash('error', 'Utilisateur non connecte');
            return $this->redirectToRoute('app_login');
        }

        $reponse = $this->reponseRepository->find($id);

        if (!$reponse) {
            $this->addFlash('error', 'Réponse non trouvée');
            return $this->redirectToRoute('quiz_index');
        }

        $quiz = $reponse->getQuestion()->getQuiz();
        $creatorId = $quiz->getIdCreateur();

        if ($creatorId !== null && $creatorId !== $currentUser->getId()) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de supprimer cette réponse');
            return $this->redirectToRoute('quiz_index');
        }

        $questionId = $reponse->getQuestion()->getId();

        if ($this->isCsrfTokenValid('delete'.$reponse->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($reponse);
            $this->entityManager->flush();
            $this->addFlash('success', 'La réponse a été supprimée avec succès !');
        }

        return $this->redirectToRoute('question_manage_reponses', ['id' => $questionId]);
    }

    // ===========================
    // STUDENT ROUTES
    // ===========================

    #[Route('/question/{idQuestion<\d+>}/liste', name: 'reponse_list', methods: ['GET'])]
    public function list(int $idQuestion): Response
    {
        $question = $this->questionRepository->find($idQuestion);

        if (!$question) {
            $this->addFlash('error', 'Question non trouvée');
            return $this->redirectToRoute('quiz_index');
        }

        $reponses = $this->reponseRepository->findBy(['question' => $idQuestion]);

        return $this->render('reponse/list.html.twig', [
            'question' => $question,
            'reponses' => $reponses,
        ]);
    }
}
