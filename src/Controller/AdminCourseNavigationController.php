<?php

namespace App\Controller;

use App\Entity\Contenu;
use App\Entity\Cours;
use App\Entity\Module;
use App\Repository\ContenuRepository;
use App\Repository\CoursRepository;
use App\Repository\ModuleRepository;
use App\Service\AuthChecker;
use App\Service\SimplePaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminCourseNavigationController extends AbstractController
{
    #[Route('/modules', name: 'app_admin_modules_list', methods: ['GET'])]
    public function modulesList(
        Request $request,
        ModuleRepository $moduleRepository,
        AuthChecker $authChecker,
        SimplePaginator $paginator
    ): Response
    {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            $this->addFlash('error', 'Acces non autorise.');
            return $this->redirectToRoute('app_home');
        }

        $filters = [
            'q' => trim((string) $request->query->get('q', '')),
            'statut' => (string) $request->query->get('statut', ''),
            'sort' => (string) $request->query->get('sort', 'ordre_asc'),
        ];

        $modules = $moduleRepository->findWithFilters($filters);
        $pagination = $paginator->paginateArray($modules, (int) $request->query->get('page', 1), 10);

        return $this->render('admin/modules_list.html.twig', [
            'modules' => $pagination['items'],
            'filters' => $filters,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/module/{id}/cours', name: 'app_admin_module_courses', methods: ['GET'])]
    public function moduleCourses(
        Module $module,
        Request $request,
        CoursRepository $coursRepository,
        AuthChecker $authChecker,
        SimplePaginator $paginator
    ): Response {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            $this->addFlash('error', 'Acces non autorise.');
            return $this->redirectToRoute('app_home');
        }

        $filters = [
            'q' => trim((string) $request->query->get('q', '')),
            'statut' => (string) $request->query->get('statut', ''),
            'sort' => (string) $request->query->get('sort', 'recent'),
        ];

        $courses = $coursRepository->findByModuleWithFilters((int) $module->getId(), $filters);
        $pagination = $paginator->paginateArray($courses, (int) $request->query->get('page', 1), 10);

        return $this->render('admin/module_courses_list.html.twig', [
            'module' => $module,
            'courses' => $pagination['items'],
            'filters' => $filters,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/cours/{id}/contenus', name: 'app_admin_cours_contents', methods: ['GET'])]
    public function coursContents(
        Cours $cours,
        Request $request,
        AuthChecker $authChecker,
        SimplePaginator $paginator
    ): Response
    {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            $this->addFlash('error', 'Acces non autorise.');
            return $this->redirectToRoute('app_home');
        }

        $contenus = $cours->getContenus()->toArray();
        usort($contenus, static fn (Contenu $a, Contenu $b) => $a->getOrdreAffichage() <=> $b->getOrdreAffichage());
        $pagination = $paginator->paginateArray($contenus, (int) $request->query->get('page', 1), 12);

        return $this->render('admin/cours_contents_list.html.twig', [
            'cours' => $cours,
            'contenus' => $pagination['items'],
            'pagination' => $pagination,
        ]);
    }

    #[Route('/module/{id}/detail', name: 'app_admin_module_show', methods: ['GET'])]
    public function moduleDetail(Module $module, AuthChecker $authChecker): Response
    {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            $this->addFlash('error', 'Acces non autorise.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('admin/module_show.html.twig', [
            'module' => $module,
        ]);
    }

    #[Route('/cours/{id}/detail', name: 'app_admin_cours_show', methods: ['GET'])]
    public function coursDetail(Cours $cours, AuthChecker $authChecker): Response
    {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            $this->addFlash('error', 'Acces non autorise.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('admin/cours_show.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/contenu/{id}/detail', name: 'app_admin_contenu_show', methods: ['GET'])]
    public function contenuDetail(Contenu $contenu, AuthChecker $authChecker): Response
    {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            $this->addFlash('error', 'Acces non autorise.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('admin/contenu_show.html.twig', [
            'contenu' => $contenu,
        ]);
    }

    #[Route('/validation', name: 'app_admin_validation_queue', methods: ['GET'])]
    public function validationQueue(
        AuthChecker $authChecker,
        CoursRepository $coursRepository,
        ContenuRepository $contenuRepository
    ): Response {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            $this->addFlash('error', 'Acces non autorise.');
            return $this->redirectToRoute('app_home');
        }

        $draftCourses = $coursRepository->findBy(['statut' => 'brouillon'], ['dateCreation' => 'DESC']);
        $privateContents = $contenuRepository->findBy(['estPublic' => false], ['dateAjout' => 'DESC']);

        return $this->render('admin/validation_queue.html.twig', [
            'draft_courses' => $draftCourses,
            'private_contents' => $privateContents,
        ]);
    }
}
