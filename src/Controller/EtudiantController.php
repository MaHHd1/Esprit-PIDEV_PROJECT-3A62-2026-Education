<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Form\EtudiantType;
use App\Repository\EtudiantRepository;
use App\Service\AuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/etudiant')]
class EtudiantController extends AbstractController
{
    #[Route('/', name: 'app_etudiant_index', methods: ['GET', 'POST'])]
    public function index(
        EtudiantRepository $etudiantRepository,
        AuthChecker $authChecker,
        Request $request
    ): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Si l'utilisateur est un étudiant, le rediriger vers son dashboard
        if ($authChecker->isEtudiant()) {
            return $this->redirectToRoute('app_etudiant_dashboard');
        }

        // Seuls les administrateurs peuvent voir la liste des étudiants
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux administrateurs.');
            return $this->redirectToRoute('app_home');
        }

        // Redirect to the combined users list instead
        return $this->redirectToRoute('app_administrateur_utilisateurs');
    }

    #[Route('/new', name: 'app_etudiant_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Seuls les administrateurs peuvent créer des étudiants
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux administrateurs.');
            return $this->redirectToRoute('app_home');
        }

        $etudiant = new Etudiant();
        $form = $this->createForm(EtudiantType::class, $etudiant);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Hash the password before saving
                $plainPassword = $form->get('motDePasse')->getData();
                $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
                $etudiant->setMotDePasse($hashedPassword);

                // Set the creation date if not already set
                if (!$etudiant->getDateCreation()) {
                    $etudiant->setDateCreation(new \DateTime());
                }

                // Set the inscription date if not already set
                if (!$etudiant->getDateInscription()) {
                    $etudiant->setDateInscription(new \DateTime());
                }

                $entityManager->persist($etudiant);
                $entityManager->flush();

                $this->addFlash('success', 'Étudiant créé avec succès !');

                // If "save and add another" button was clicked, stay on the same page
                if ($request->request->has('save_and_new')) {
                    return $this->redirectToRoute('app_etudiant_new');
                }

                // Otherwise redirect to the show page
                return $this->redirectToRoute('app_administrateur_utilisateur_show', [
                    'type' => 'etudiant',
                    'id' => $etudiant->getId(),
                ], Response::HTTP_SEE_OTHER);
            } else {
                // Form has validation errors
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire. Les données saisies ne sont pas valides.');
            }
        }

        return $this->render('admin/etudiant_new.html.twig', [
            'etudiant' => $etudiant,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_etudiant_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        EtudiantRepository $etudiantRepository,
        AuthChecker $authChecker
    ): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Seuls les administrateurs peuvent voir les détails des étudiants
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux administrateurs.');
            return $this->redirectToRoute('app_home');
        }

        $etudiant = $etudiantRepository->find($id);

        if (!$etudiant) {
            $this->addFlash('error', 'Étudiant non trouvé.');
            return $this->redirectToRoute('app_administrateur_utilisateurs');
        }

        // Redirect to the combined user show page
        return $this->redirectToRoute('app_administrateur_utilisateur_show', [
            'type' => 'etudiant',
            'id' => $etudiant->getId(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_etudiant_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        int $id,
        EtudiantRepository $etudiantRepository,
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Seuls les administrateurs peuvent modifier les étudiants
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux administrateurs.');
            return $this->redirectToRoute('app_home');
        }

        $etudiant = $etudiantRepository->find($id);

        if (!$etudiant) {
            $this->addFlash('error', 'Étudiant non trouvé.');
            return $this->redirectToRoute('app_administrateur_utilisateurs');
        }

        $form = $this->createForm(EtudiantType::class, $etudiant, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Update password only if provided and not empty
                if ($form->has('motDePasse') && $form->get('motDePasse')->getData()) {
                    $plainPassword = $form->get('motDePasse')->getData();
                    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
                    $etudiant->setMotDePasse($hashedPassword);
                }

                $entityManager->flush();

                $this->addFlash('success', 'Étudiant modifié avec succès !');

                // If "save and continue" button was clicked, stay on the same page
                if ($request->request->has('save_and_continue')) {
                    return $this->redirectToRoute('app_etudiant_edit', ['id' => $etudiant->getId()]);
                }

                // Otherwise redirect to the show page
                return $this->redirectToRoute('app_administrateur_utilisateur_show', [
                    'type' => 'etudiant',
                    'id' => $etudiant->getId(),
                ], Response::HTTP_SEE_OTHER);
            } else {
                // Form has validation errors
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire. Les données saisies ne sont pas valides.');
            }
        }

        return $this->render('admin/etudiant_edit.html.twig', [
            'etudiant' => $etudiant,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_etudiant_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        Request $request,
        int $id,
        EtudiantRepository $etudiantRepository,
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Seuls les administrateurs peuvent supprimer des étudiants
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux administrateurs.');
            return $this->redirectToRoute('app_home');
        }

        $etudiant = $etudiantRepository->find($id);

        if (!$etudiant) {
            $this->addFlash('error', 'Étudiant non trouvé.');
            return $this->redirectToRoute('app_administrateur_utilisateurs');
        }

        if ($this->isCsrfTokenValid('delete'.$etudiant->getId(), $request->request->get('_token'))) {
            $entityManager->remove($etudiant);
            $entityManager->flush();

            $this->addFlash('success', 'Étudiant supprimé avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide. La suppression a été annulée.');
        }

        return $this->redirectToRoute('app_administrateur_utilisateurs', [], Response::HTTP_SEE_OTHER);
    }
}
