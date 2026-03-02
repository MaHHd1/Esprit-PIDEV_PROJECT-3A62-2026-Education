<?php
// src/Controller/EvaluationController.php
namespace App\Controller;

use App\Entity\Evaluation;
use App\Form\EvaluationType;
use App\Repository\EvaluationRepository;
use App\Service\AuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/evaluation')]
class EvaluationController extends AbstractController
{
    #[Route('/', name: 'app_evaluation_index', methods: ['GET'])]
    public function index(
        Request $request, 
        EvaluationRepository $evaluationRepository,
        AuthChecker $authChecker
    ): Response
    {
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $authChecker->getCurrentUser();
        if (!$user instanceof \App\Entity\Enseignant) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $user;

        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'dateCreation');
        $order = $request->query->get('order', 'DESC');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, $request->query->getInt('limit', 20));

        // Recherche et tri filtrés par enseignant avec pagination
        $evaluations = $evaluationRepository->findBySearchAndSort($search, $sortBy, $order, $enseignant->getId(), $page, $limit);
        
        // Compter le nombre total de résultats pour la pagination
        $totalEvaluations = $evaluationRepository->countBySearchAndSort($search, $enseignant->getId());
        $totalPages = ceil($totalEvaluations / $limit);

        return $this->render('evaluation/index.html.twig', [
            'evaluations' => $evaluations,
            'search' => $search,
            'sortBy' => $sortBy,
            'order' => $order,
            'enseignant' => $enseignant,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalEvaluations' => $totalEvaluations,
            'limit' => $limit,
        ]);
    }

    #[Route('/new', name: 'app_evaluation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $authChecker->getCurrentUser();
        if (!$user instanceof \App\Entity\Enseignant) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $user;

        $evaluation = new Evaluation();
        $evaluation->setIdEnseignant($enseignant->getId());
        $evaluation->setDateCreation(new \DateTime());
        $evaluation->setStatut('ouverte');

        $form = $this->createForm(EvaluationType::class, $evaluation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evaluation);
            $entityManager->flush();

            $this->addFlash('success', 'Évaluation créée avec succès !');
            return $this->redirectToRoute('app_evaluation_index');
        }

        return $this->render('evaluation/new.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form->createView(),
            'enseignant' => $enseignant,
        ]);
    }

    #[Route('/{id}/show', name: 'app_evaluation_show', methods: ['GET'])]
    public function show(
        Evaluation $evaluation,
        AuthChecker $authChecker
    ): Response
    {
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $authChecker->getCurrentUser();
        if (!$user instanceof \App\Entity\Enseignant) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $user;

        if ($evaluation->getIdEnseignant() !== (string)$enseignant->getId()) {
            $this->addFlash('error', 'Accès non autorisé. Cette évaluation ne vous appartient pas.');
            return $this->redirectToRoute('app_evaluation_index');
        }

        return $this->render('evaluation/show.html.twig', [
            'evaluation' => $evaluation,
            'enseignant' => $enseignant,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_evaluation_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Evaluation $evaluation,
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $authChecker->getCurrentUser();
        if (!$user instanceof \App\Entity\Enseignant) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $user;

        if ($evaluation->getIdEnseignant() !== (string)$enseignant->getId()) {
            $this->addFlash('error', 'Accès non autorisé. Cette évaluation ne vous appartient pas.');
            return $this->redirectToRoute('app_evaluation_index');
        }

        $form = $this->createForm(EvaluationType::class, $evaluation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Évaluation modifiée avec succès !');
            return $this->redirectToRoute('app_evaluation_index');
        }

        return $this->render('evaluation/edit.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form->createView(),
            'enseignant' => $enseignant,
        ]);
    }

    #[Route('/{id}', name: 'app_evaluation_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Evaluation $evaluation,
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $authChecker->getCurrentUser();
        if (!$user instanceof \App\Entity\Enseignant) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $user;

        if ($evaluation->getIdEnseignant() !== (string)$enseignant->getId()) {
            $this->addFlash('error', 'Accès non autorisé. Cette évaluation ne vous appartient pas.');
            return $this->redirectToRoute('app_evaluation_index');
        }

        if ($this->isCsrfTokenValid('delete'.$evaluation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($evaluation);
            $entityManager->flush();
            
            $this->addFlash('success', 'Évaluation supprimée avec succès !');
        }

        return $this->redirectToRoute('app_evaluation_index');
    }
}
