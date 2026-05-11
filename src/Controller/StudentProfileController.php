<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Form\EtudiantType;
use App\Form\ChangePasswordType;
use App\Service\AuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/etudiant')]
class StudentProfileController extends AbstractController
{
    #[Route('/profile/edit', name: 'app_etudiant_edit_profile', methods: ['GET', 'POST'])]
    public function editProfile(
        Request $request,
        AuthChecker $authChecker,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur est un étudiant
        if (!$authChecker->isEtudiant()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux étudiants.');
            return $this->redirectToRoute('app_home');
        }

        // Récupérer l'étudiant connecté
        $student = $authChecker->getCurrentUser();

        if (!$student instanceof Etudiant) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('app_home');
        }

        // Créer le formulaire avec l'option is_edit
        $form = $this->createForm(EtudiantType::class, $student, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Mettre à jour le mot de passe seulement si fourni
                if ($form->has('motDePasse') && $form->get('motDePasse')->getData()) {
                    $plainPassword = $form->get('motDePasse')->getData();
                    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
                    $student->setMotDePasse($hashedPassword);
                }

                $entityManager->flush();

                $this->addFlash('success', 'Votre profil a été mis à jour avec succès !');
                return $this->redirectToRoute('app_etudiant_profile');
            } else {
                // Form has validation errors
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire. Les données saisies ne sont pas valides.');
            }
        }

        return $this->render('etudiant/edit_profile.html.twig', [
            'student' => $student,
            'form' => $form->createView(),
            'current_user' => $student,
            'is_etudiant' => true,
            'is_admin' => false,
            'is_enseignant' => false,
        ]);
    }

    #[Route('/profile', name: 'app_etudiant_profile', methods: ['GET'])]
    public function profile(AuthChecker $authChecker): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour accéder à votre profil.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur est un étudiant
        if (!$authChecker->isEtudiant()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux étudiants.');
            return $this->redirectToRoute('app_home');
        }

        $student = $authChecker->getCurrentUser();

        if (!$student instanceof Etudiant) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('etudiant/profile.html.twig', [
            'student' => $student,
            'current_user' => $student,
            'is_etudiant' => true,
            'is_admin' => false,
            'is_enseignant' => false,
        ]);
    }

    #[Route('/dashboard', name: 'app_etudiant_dashboard_profile', methods: ['GET'])]
    public function dashboard(AuthChecker $authChecker): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour accéder au dashboard.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur est un étudiant
        if (!$authChecker->isEtudiant()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux étudiants.');
            return $this->redirectToRoute('app_home');
        }

        $student = $authChecker->getCurrentUser();

        if (!$student instanceof Etudiant) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('etudiant/dashboard.html.twig', [
            'student' => $student,
            'current_user' => $student,
            'is_etudiant' => true,
            'is_admin' => false,
            'is_enseignant' => false,
        ]);
    }
}
