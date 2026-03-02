<?php

namespace App\Controller;

use App\Repository\EtudiantRepository;
use App\Repository\EnseignantRepository;
use App\Repository\AdministrateurRepository;
use App\Service\AuthChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        EtudiantRepository $etudiantRepo,
        EnseignantRepository $enseignantRepo,
        AdministrateurRepository $adminRepo,
        AuthChecker $authChecker
    ): Response
    {
        // Use optimized count methods for better performance (avoids unnecessary JOINs)
        $stats = [
            'etudiants' => $etudiantRepo->countOptimized(),
            'enseignants' => $enseignantRepo->countOptimized(),
            'administrateurs' => $adminRepo->countOptimized(),
        ];

        // Get current user from AuthChecker
        $user = $authChecker->getCurrentUser();

        // Get user info for template
        $userName = null;
        $userEmail = null;
        $userType = null;

        if ($user) {
            $userName = method_exists($user, 'getNomComplet') ? $user->getNomComplet() : ($user->getPrenom() . ' ' . $user->getNom());
            $userEmail = $user->getEmail();
            $userType = method_exists($user, 'getType') ? $user->getType() : 'unknown';
        }

        return $this->render('home/index.html.twig', [
            'stats' => $stats,
            'current_user' => $user,
            'user_name' => $userName,
            'user_email' => $userEmail,
            'user_type' => $userType,
            'is_admin' => $authChecker->isAdmin(),
            'is_enseignant' => $authChecker->isEnseignant(),
            'is_etudiant' => $authChecker->isEtudiant(),
        ]);
    }
}