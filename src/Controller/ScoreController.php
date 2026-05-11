<?php
// src/Controller/ScoreController.php
namespace App\Controller;

use App\Entity\Score;
use App\Form\ScoreType;
use App\Repository\ScoreRepository;
use App\Repository\SoumissionRepository;
use App\Service\AuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/score')]
class ScoreController extends AbstractController
{
    // 📖 INDEX - Accessible par ÉTUDIANT et ENSEIGNANT
    #[Route('/', name: 'app_score_index', methods: ['GET'])]
    public function index(
        Request $request, 
        ScoreRepository $scoreRepository,
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

        // Filtrer selon le type d'utilisateur
        if ($user instanceof \App\Entity\Etudiant) {
            // Étudiant : voir uniquement SES scores
            $scores = $scoreRepository->findByStudent((string)$user->getId(), $search, $sortBy, $order);
            
            return $this->render('score/index.html.twig', [
                'scores' => $scores,
                'search' => $search,
                'sortBy' => $sortBy,
                'order' => $order,
                'student' => $user,
                'user_type' => 'etudiant',
            ]);
            
        } elseif ($user instanceof \App\Entity\Enseignant) {
            // Enseignant : voir les SOUMISSIONS de ses évaluations
            $soumissions = $soumissionRepository->findByTeacher((string)$user->getId(), $search, $sortBy, $order);
            
            return $this->render('score/index.html.twig', [
                'soumissions' => $soumissions,
                'search' => $search,
                'sortBy' => $sortBy,
                'order' => $order,
                'enseignant' => $user,
                'user_type' => 'enseignant',
            ]);
        }
        
        // Utilisateur non reconnu
        $this->addFlash('error', 'Type d\'utilisateur non reconnu.');
        return $this->redirectToRoute('app_home');
    }

    // ➕ NEW - RÉSERVÉ aux ENSEIGNANTS uniquement
    #[Route('/new', name: 'app_score_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        SoumissionRepository $soumissionRepository,
        AuthChecker $authChecker
    ): Response
    {
        // ========== AUTHENTICATION ==========
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }
        
        $user = $authChecker->getCurrentUser();
        
        // ❌ ÉTUDIANT INTERDIT
        if (!$user instanceof \App\Entity\Enseignant) {
            $this->addFlash('error', 'Seuls les enseignants peuvent corriger des soumissions.');
            return $this->redirectToRoute('app_home');
        }
        
        $enseignant = $user;
        
        $score = new Score();
        $score->setDateCorrection(new \DateTime());
        $score->setStatutCorrection('corrige');
        
        // Pré-sélectionner une soumission si fournie en query (?soumission=ID)
        $soumissionId = $request->query->get('soumission');
        if ($soumissionId) {
            $soumission = $soumissionRepository->find($soumissionId);
            if ($soumission) {
                // 🔐 SÉCURITÉ : Vérifier que l'enseignant possède cette évaluation
                if ($soumission->getEvaluation()->getIdEnseignant() !== (string)$enseignant->getId()) {
                    $this->addFlash('danger', 'Vous ne pouvez corriger que les soumissions de vos propres évaluations.');
                    return $this->redirectToRoute('app_score_index');
                }
                $score->setSoumission($soumission);
            }
        }
        
        $form = $this->createForm(ScoreType::class, $score);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $soumission = $score->getSoumission();

            // 🔐 SÉCURITÉ : Vérifier que l'enseignant possède cette évaluation
            if ($soumission->getEvaluation()->getIdEnseignant() !== (string)$enseignant->getId()) {
                $this->addFlash('danger', 'Vous ne pouvez corriger que les soumissions de vos propres évaluations.');
                return $this->redirectToRoute('app_score_index');
            }

            // 🔐 SÉCURITÉ : 1 soumission = 1 score maximum
            if ($soumission->getScore() !== null) {
                $this->addFlash('danger', 'Cette soumission a déjà été corrigée. Utilisez la modification pour changer la note.');
                return $this->redirectToRoute('app_score_index');
            }

            // 🔗 Liaison bidirectionnelle
            $soumission->setScore($score);

            $entityManager->persist($score);
            $entityManager->flush();

            $this->addFlash('success', 'Score ajouté avec succès ! L\'étudiant peut maintenant voir sa note.');
            return $this->redirectToRoute('app_score_index');
        }

        return $this->render('score/new.html.twig', [
            'score' => $score,
            'form' => $form,
            'enseignant' => $enseignant,
        ]);
    }

    // 📖 SHOW - Accessible par ÉTUDIANT (ses notes) et ENSEIGNANT (ses corrections)
    #[Route('/{id}', name: 'app_score_show', methods: ['GET'])]
    public function show(
        Score $score,
        AuthChecker $authChecker
    ): Response
    {
        // ========== AUTHENTICATION ==========
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }
        
        $user = $authChecker->getCurrentUser();
        
        // Vérifier les droits d'accès
        if ($user instanceof \App\Entity\Etudiant) {
            // L'étudiant ne peut voir que SES scores
            if ($score->getSoumission()->getIdEtudiant() !== (string)$user->getId()) {
                $this->addFlash('error', 'Vous ne pouvez voir que vos propres notes.');
                return $this->redirectToRoute('app_score_index');
            }
            
            return $this->render('score/show.html.twig', [
                'score' => $score,
                'student' => $user,
            ]);
            
        } elseif ($user instanceof \App\Entity\Enseignant) {
            // L'enseignant peut voir les scores de SES évaluations
            if ($score->getSoumission()->getEvaluation()->getIdEnseignant() !== (string)$user->getId()) {
                $this->addFlash('error', 'Vous ne pouvez voir que les scores de vos propres évaluations.');
                return $this->redirectToRoute('app_score_index');
            }
            
            return $this->render('score/show.html.twig', [
                'score' => $score,
                'enseignant' => $user,
            ]);
        }
        
        // Utilisateur non reconnu
        $this->addFlash('error', 'Accès non autorisé.');
        return $this->redirectToRoute('app_home');
    }

    // ✏️ EDIT - RÉSERVÉ aux ENSEIGNANTS uniquement
    #[Route('/{id}/edit', name: 'app_score_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Score $score, 
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        // ========== AUTHENTICATION ==========
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }
        
        $user = $authChecker->getCurrentUser();
        
        // ❌ ÉTUDIANT INTERDIT
        if (!$user instanceof \App\Entity\Enseignant) {
            $this->addFlash('error', 'Seuls les enseignants peuvent modifier les scores.');
            return $this->redirectToRoute('app_home');
        }
        
        $enseignant = $user;
        
        // 🔐 SÉCURITÉ : Vérifier que c'est SON évaluation
        if ($score->getSoumission()->getEvaluation()->getIdEnseignant() !== (string)$enseignant->getId()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que les scores de vos propres évaluations.');
            return $this->redirectToRoute('app_score_index');
        }
        
        $form = $this->createForm(ScoreType::class, $score, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 🔐 SÉCURITÉ : Empêcher le changement de soumission
            $originalSoumission = $score->getSoumission();
            if ($originalSoumission->getScore() !== $score) {
                $this->addFlash('danger', 'Impossible de modifier la soumission associée à ce score.');
                return $this->redirectToRoute('app_score_index');
            }

            // Définir le statut et la date de correction
            $score->setStatutCorrection('corrige');
            $score->setDateCorrection(new \DateTime());

            $entityManager->flush();

            $this->addFlash('success', 'Score modifié avec succès !');
            return $this->redirectToRoute('app_score_index');
        }

        return $this->render('score/edit.html.twig', [
            'score' => $score,
            'form' => $form,
            'enseignant' => $enseignant,
        ]);
    }

    // 🗑️ DELETE - RÉSERVÉ aux ENSEIGNANTS uniquement
    #[Route('/{id}/delete', name: 'app_score_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Score $score, 
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        // ========== AUTHENTICATION ==========
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }
        
        $user = $authChecker->getCurrentUser();
        
        // ❌ ÉTUDIANT INTERDIT
        if (!$user instanceof \App\Entity\Enseignant) {
            $this->addFlash('error', 'Seuls les enseignants peuvent supprimer les scores.');
            return $this->redirectToRoute('app_home');
        }
        
        $enseignant = $user;
        
        // 🔐 SÉCURITÉ : Vérifier que c'est SON évaluation
        if ($score->getSoumission()->getEvaluation()->getIdEnseignant() !== (string)$enseignant->getId()) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que les scores de vos propres évaluations.');
            return $this->redirectToRoute('app_score_index');
        }
        
        if ($this->isCsrfTokenValid('delete' . $score->getId(), $request->request->get('_token'))) {
            // 🔗 Casser la relation OneToOne
            if ($score->getSoumission()) {
                $score->getSoumission()->setScore(null);
            }

            $entityManager->remove($score);
            $entityManager->flush();

            $this->addFlash('success', 'Score supprimé avec succès !');
        }

        return $this->redirectToRoute('app_score_index');
    }
}