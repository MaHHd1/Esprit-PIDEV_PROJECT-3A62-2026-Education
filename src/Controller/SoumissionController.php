<?php
// src/Controller/SoumissionController.php
namespace App\Controller;

use App\Entity\Soumission;
use App\Form\SoumissionType;
use App\Repository\SoumissionRepository;
use App\Service\AuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/soumission')]
class SoumissionController extends AbstractController
{
    #[Route('/', name: 'app_soumission_index', methods: ['GET'])]
    public function index(
        Request $request, 
        SoumissionRepository $soumissionRepository,
        AuthChecker $authChecker
    ): Response
    {
        // ========== AUTHENTICATION ==========
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }
        
        $user = $authChecker->getCurrentUser();
        
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'dateSoumission');
        $order = $request->query->get('order', 'DESC');
        
        // Récupérer toutes les soumissions (pour l'instant)
        $soumissions = $soumissionRepository->findBySearchAndSort($search, $sortBy, $order);
        
        // Préparer les variables pour le template selon le type d'utilisateur
        $templateVars = [
            'soumissions' => $soumissions,
            'search' => $search,
            'sortBy' => $sortBy,
            'order' => $order,
        ];
        
        // Ajouter la variable appropriée selon le type d'utilisateur
        if ($user instanceof \App\Entity\Etudiant) {
            $templateVars['student'] = $user;
        } elseif ($user instanceof \App\Entity\Enseignant) {
            $templateVars['enseignant'] = $user;
        }
        
        return $this->render('soumission/index.html.twig', $templateVars);
    }

    #[Route('/new', name: 'app_soumission_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        // ========== AUTHENTICATION ==========
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }
        
        $user = $authChecker->getCurrentUser();
        
        // Seuls les étudiants peuvent soumettre
        if (!$user instanceof \App\Entity\Etudiant) {
            $this->addFlash('error', 'Seuls les étudiants peuvent soumettre des travaux.');
            return $this->redirectToRoute('app_home');
        }
        
        $etudiant = $user;
        
        $soumission = new Soumission();
        
        // Auto-remplir l'ID étudiant et la date
        $soumission->setIdEtudiant((string)$etudiant->getId());
        $soumission->setDateSoumission(new \DateTime());
        
        $form = $this->createForm(SoumissionType::class, $soumission);
        $form->handleRequest($request);

        // ⚠️ Vérifications après submit
        if ($form->isSubmitted()) {
            $evaluation = $soumission->getEvaluation();

            if ($evaluation && $evaluation->getStatut() === 'fermee') {
                $this->addFlash('danger', 'Cette évaluation est fermée. Vous ne pouvez plus soumettre.');
                return $this->redirectToRoute('app_evaluation_index');
            }
            
            // Vérifier le retard
            if ($evaluation && $evaluation->getDateLimite() < new \DateTime()) {
                $soumission->setStatut('en_retard');
            } else {
                $soumission->setStatut('soumise');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($soumission);
            $entityManager->flush();

            $this->addFlash('success', 'Travail soumis avec succès !');
            return $this->redirectToRoute('app_soumission_index');
        }

        return $this->render('soumission/new.html.twig', [
            'soumission' => $soumission,
            'form' => $form,
            'student' => $etudiant,
        ]);
    }

    #[Route('/{id}', name: 'app_soumission_show', methods: ['GET'])]
    public function show(
        Soumission $soumission,
        AuthChecker $authChecker
    ): Response
    {
        // ========== AUTHENTICATION ==========
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }
        
        $user = $authChecker->getCurrentUser();
        
        // Vérifier les droits d'accès
        $canView = false;
        
        if ($user instanceof \App\Entity\Etudiant) {
            // L'étudiant ne peut voir que SES soumissions
            $canView = ($soumission->getIdEtudiant() === (string)$user->getId());
            $templateVar = ['student' => $user];
            
        } elseif ($user instanceof \App\Entity\Enseignant) {
            // L'enseignant peut voir les soumissions de SES évaluations
            $canView = ($soumission->getEvaluation()->getIdEnseignant() === (string)$user->getId());
            $templateVar = ['enseignant' => $user];
        }
        
        if (!$canView) {
            $this->addFlash('error', 'Accès non autorisé à cette soumission.');
            return $this->redirectToRoute('app_soumission_index');
        }
        
        return $this->render('soumission/show.html.twig', array_merge([
            'soumission' => $soumission,
        ], $templateVar));
    }

    #[Route('/{id}/edit', name: 'app_soumission_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Soumission $soumission, 
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        // ========== AUTHENTICATION ==========
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }
        
        $user = $authChecker->getCurrentUser();
        
        // Seuls les étudiants peuvent modifier leurs soumissions
        if (!$user instanceof \App\Entity\Etudiant) {
            $this->addFlash('error', 'Seuls les étudiants peuvent modifier leurs soumissions.');
            return $this->redirectToRoute('app_home');
        }
        
        $etudiant = $user;
        
        // Vérifier que c'est SA soumission
        if ($soumission->getIdEtudiant() !== (string)$etudiant->getId()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres soumissions.');
            return $this->redirectToRoute('app_soumission_index');
        }
        
        // 🔒 Vérification AVANT le formulaire
        if ($soumission->getEvaluation()->getStatut() === 'fermee') {
            $this->addFlash('danger', 'Cette évaluation est fermée. Modification interdite.');
            return $this->redirectToRoute('app_soumission_index');
        }

        $form = $this->createForm(SoumissionType::class, $soumission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Soumission modifiée avec succès !');
            return $this->redirectToRoute('app_soumission_index');
        }

        return $this->render('soumission/edit.html.twig', [
            'soumission' => $soumission,
            'form' => $form,
            'student' => $etudiant,
        ]);
    }

    #[Route('/{id}', name: 'app_soumission_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Soumission $soumission, 
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        // ========== AUTHENTICATION ==========
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }
        
        $user = $authChecker->getCurrentUser();
        
        // Vérifier les droits
        $canDelete = false;
        
        if ($user instanceof \App\Entity\Etudiant) {
            // L'étudiant ne peut supprimer que SES soumissions
            $canDelete = ($soumission->getIdEtudiant() === (string)$user->getId());
            
        } elseif ($user instanceof \App\Entity\Enseignant) {
            // L'enseignant peut supprimer les soumissions de SES évaluations
            $canDelete = ($soumission->getEvaluation()->getIdEnseignant() === (string)$user->getId());
        }
        
        if (!$canDelete) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer cette soumission.');
            return $this->redirectToRoute('app_soumission_index');
        }
        
        if ($this->isCsrfTokenValid('delete'.$soumission->getId(), $request->request->get('_token'))) {
            $entityManager->remove($soumission);
            $entityManager->flush();
            
            $this->addFlash('success', 'Soumission supprimée avec succès !');
        }

        return $this->redirectToRoute('app_soumission_index');
    }
}