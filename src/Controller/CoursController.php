<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Form\CoursType;
use App\Repository\CoursRepository;
use App\Repository\EnseignantRepository;
use App\Service\ActivityLogger;
use App\Service\AuthChecker;
use App\Service\CourseNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/cours')]
class CoursController extends AbstractController
{
    #[Route('/', name: 'app_cours_index', methods: ['GET'])]
    public function index(CoursRepository $coursRepository, AuthChecker $authChecker): Response
    {
        if ($authChecker->isLoggedIn() && ($authChecker->isAdmin() || $authChecker->isEnseignant())) {
            $cours = $coursRepository->findBy([], ['titre' => 'ASC']);
        } else {
            $cours = $coursRepository->findBy(['statut' => 'ouvert'], ['titre' => 'ASC']);
        }

        return $this->render('cours/index.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/{id}', name: 'app_cours_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, CoursRepository $coursRepository, AuthChecker $authChecker): Response
    {
        $cours = $coursRepository->find($id);

        if (!$cours) {
            $this->addFlash('error', 'Cours non trouve.');
            return $this->redirectToRoute('app_cours_index');
        }

        $canAccess = $cours->getStatut() === 'ouvert';
        if (!$canAccess && $authChecker->isLoggedIn()) {
            if ($authChecker->isAdmin()) {
                $canAccess = true;
            } elseif ($authChecker->isEnseignant()) {
                $currentUser = $authChecker->getCurrentUser();
                if ($currentUser) {
                    $canAccess = $cours->getEnseignants()->contains($currentUser);
                }
            }
        }

        if (!$canAccess) {
            $this->addFlash('error', 'Ce cours est masque pour les etudiants.');
            return $this->redirectToRoute('app_cours_index');
        }

        return $this->render('cours/show.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/admin', name: 'app_admin_cours_index', methods: ['GET'])]
    public function adminIndex(CoursRepository $coursRepository, AuthChecker $authChecker): Response
    {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            $this->addFlash('error', 'Acces non autorise.');
            return $this->redirectToRoute('app_home');
        }

        $cours = $coursRepository->findBy([], ['dateCreation' => 'DESC']);

        return $this->render('admin/cours_index.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/admin/new', name: 'app_admin_cours_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        AuthChecker $authChecker,
        EnseignantRepository $ensRepo,
        ActivityLogger $activityLogger
    ): Response {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            $this->addFlash('error', 'Acces non autorise.');
            return $this->redirectToRoute('app_home');
        }

        $cours = new Cours();
        $filters = [
            'search' => $request->query->get('search_ens', ''),
            'specialite' => $request->query->get('specialite', ''),
        ];
        $form = $this->createForm(CoursType::class, $cours, [
            'enseignant_filters' => $filters,
            'prerequis_data' => '',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cours->setPrerequis($this->parsePrerequisIds((string) $form->get('prerequisIds')->getData()));
            $em->persist($cours);
            $em->flush();
            $user = $authChecker->getCurrentUser();
            $activityLogger->log($user, 'course_create', 'cours', (int) $cours->getId(), [
                'code' => $cours->getCodeCours(),
            ]);

            $this->addFlash('success', 'Cours cree avec succes.');
            $module = $cours->getModule();

            if ($module) {
                return $this->redirectToRoute('app_admin_module_courses', ['id' => $module->getId()]);
            }

            return $this->redirectToRoute('app_admin_modules_list');
        }

        return $this->render('admin/cours_new.html.twig', [
            'form' => $form->createView(),
            'cours' => $cours,
            'search_ens' => $filters['search'],
            'specialite' => $filters['specialite'],
            'specialites' => $ensRepo->findUniqueSpecialites(),
        ]);
    }

    #[Route('/admin/{id}/edit', name: 'app_admin_cours_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        int $id,
        Request $request,
        CoursRepository $coursRepository,
        EntityManagerInterface $em,
        AuthChecker $authChecker,
        EnseignantRepository $ensRepo,
        ActivityLogger $activityLogger,
        CourseNotificationService $notificationService
    ): Response {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            $this->addFlash('error', 'Acces non autorise.');
            return $this->redirectToRoute('app_home');
        }

        $cours = $coursRepository->find($id);
        if (!$cours) {
            $this->addFlash('error', 'Cours introuvable.');
            return $this->redirectToRoute('app_admin_modules_list');
        }

        $filters = [
            'search' => $request->query->get('search_ens', ''),
            'specialite' => $request->query->get('specialite', ''),
        ];
        $form = $this->createForm(CoursType::class, $cours, [
            'enseignant_filters' => $filters,
            'prerequis_data' => implode(',', (array) ($cours->getPrerequis() ?? [])),
        ]);
        $previousStatus = $cours->getStatut();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cours->setPrerequis($this->parsePrerequisIds((string) $form->get('prerequisIds')->getData()));
            $em->flush();
            $user = $authChecker->getCurrentUser();
            $activityLogger->log($user, 'course_edit', 'cours', (int) $cours->getId(), [
                'code' => $cours->getCodeCours(),
            ]);
            if ($previousStatus !== $cours->getStatut() && in_array($cours->getStatut(), ['ouvert', 'brouillon'], true)) {
                $notificationService->notifyCourseVisibilityChanged($cours, $cours->getStatut() === 'ouvert');
            }
            $this->addFlash('success', 'Cours mis a jour.');
            $module = $cours->getModule();

            if ($module) {
                return $this->redirectToRoute('app_admin_module_courses', ['id' => $module->getId()]);
            }

            return $this->redirectToRoute('app_admin_modules_list');
        }

        return $this->render('admin/cours_edit.html.twig', [
            'form' => $form->createView(),
            'cours' => $cours,
            'search_ens' => $filters['search'],
            'specialite' => $filters['specialite'],
            'specialites' => $ensRepo->findUniqueSpecialites(),
        ]);
    }

    #[Route('/admin/{id}/delete', name: 'app_admin_cours_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        int $id,
        Request $request,
        CoursRepository $coursRepository,
        EntityManagerInterface $em,
        AuthChecker $authChecker,
        ActivityLogger $activityLogger
    ): Response {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            $this->addFlash('error', 'Acces non autorise.');
            return $this->redirectToRoute('app_home');
        }

        $cours = $coursRepository->find($id);
        if ($cours) {
            $deletedId = (int) $cours->getId();
            $code = $cours->getCodeCours();
            $em->remove($cours);
            $em->flush();
            $user = $authChecker->getCurrentUser();
            $activityLogger->log($user, 'course_delete', 'cours', $deletedId, ['code' => $code]);
            $this->addFlash('success', 'Cours supprime.');
        }

        return $this->redirectToRoute('app_admin_modules_list');
    }

    /**
     * @return int[]
     */
    private function parsePrerequisIds(string $raw): array
    {
        $parts = array_filter(array_map('trim', explode(',', $raw)));
        $ids = [];
        foreach ($parts as $part) {
            if (ctype_digit($part)) {
                $ids[] = (int) $part;
            }
        }

        return array_values(array_unique($ids));
    }
}
