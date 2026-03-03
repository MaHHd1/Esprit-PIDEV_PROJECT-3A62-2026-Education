<?php

namespace App\Controller;

use App\Entity\Module;
use App\Form\ModuleType;
use App\Repository\ModuleRepository;
use App\Service\AuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/module')]
class ModuleController extends AbstractController
{
    #[Route('/{id}', name: 'app_module_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function show(int $id, ModuleRepository $moduleRepository): Response
    {
        $module = $moduleRepository->find($id);

        if (!$module) {
            $this->addFlash('error', 'Module non trouvé.');
            return $this->redirectToRoute('app_cours_index');
        }

        return $this->render('module/show.html.twig', [
            'module' => $module,
        ]);
    }

    // This action is now handled by AdminCourseNavigationController::modulesList()
    // which uses the route name 'app_admin_modules_list'

    #[Route('/admin/new', name: 'app_admin_module_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em, AuthChecker $authChecker): Response
    {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }

        $module = new Module();
        $form = $this->createForm(ModuleType::class, $module);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($module);
            $em->flush();

            $this->addFlash('success', 'Module créé.');
            return $this->redirectToRoute('app_admin_modules_list');
        }

        return $this->render('admin/module_new.html.twig', [
            'form' => $form->createView(),
            'module' => $module,
        ]);
    }

    #[Route('/admin/{id}/edit', name: 'app_admin_module_edit', methods: ['GET','POST'], requirements: ['id' => '\\d+'])]
    public function edit(int $id, Request $request, ModuleRepository $moduleRepository, EntityManagerInterface $em, AuthChecker $authChecker): Response
    {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }

        $module = $moduleRepository->find($id);
        if (!$module) {
            $this->addFlash('error', 'Module introuvable.');
            return $this->redirectToRoute('app_admin_modules_list');
        }

        $form = $this->createForm(ModuleType::class, $module);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Module mis à jour.');
            return $this->redirectToRoute('app_admin_modules_list');
        }

        return $this->render('admin/module_edit.html.twig', [
            'form' => $form->createView(),
            'module' => $module,
        ]);
    }

    #[Route('/admin/{id}/delete', name: 'app_admin_module_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(int $id, Request $request, ModuleRepository $moduleRepository, EntityManagerInterface $em, AuthChecker $authChecker): Response
    {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }

        $module = $moduleRepository->find($id);
        if ($module) {
            $em->remove($module);
            $em->flush();
            $this->addFlash('success', 'Module supprimé.');
        }

        return $this->redirectToRoute('app_admin_modules_list');
    }
}
