<?php

namespace App\Controller;

use App\Entity\Contenu;
use App\Entity\Quiz;
use App\Form\ContenuType;
use App\Repository\ContenuRepository;
use App\Repository\QuizRepository;
use App\Service\ActivityLogger;
use App\Service\AuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/contenu')]
class ContenuController extends AbstractController
{
    #[Route('/{id}', name: 'app_contenu_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, ContenuRepository $contenuRepository, EntityManagerInterface $em, AuthChecker $authChecker): Response
    {
        $contenu = $contenuRepository->find($id);

        if (!$contenu) {
            $this->addFlash('error', 'Contenu non trouve.');
            return $this->redirectToRoute('app_cours_index');
        }

        $course = $contenu->getCours();

        if ($authChecker->isLoggedIn() && $authChecker->isEtudiant()) {
            $this->addFlash('info', 'Le detail complet du contenu est reserve aux enseignants et administrateurs.');

            if ($course) {
                return $this->redirectToRoute('app_etudiant_course_show', ['id' => $course->getId()]);
            }

            return $this->redirectToRoute('app_etudiant_courses');
        }

        $canAccess = $course && $course->getStatut() === 'ouvert' && $contenu->isEstPublic();

        if (!$canAccess && $authChecker->isLoggedIn()) {
            if ($authChecker->isAdmin()) {
                $canAccess = true;
            } elseif ($authChecker->isEnseignant()) {
                $currentUser = $authChecker->getCurrentUser();
                if ($currentUser && $course) {
                    $canAccess = $course->getEnseignants()->contains($currentUser);
                }
            }
        }

        if (!$canAccess) {
            $this->addFlash('error', 'Ce contenu est masque pour les etudiants.');
            return $this->redirectToRoute('app_cours_index');
        }

        $contenu->setNombreVues($contenu->getNombreVues() + 1);
        $em->flush();

        return $this->render('contenu/show.html.twig', [
            'contenu' => $contenu,
        ]);
    }

    #[Route('/admin/new', name: 'app_admin_contenu_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        QuizRepository $quizRepository,
        AuthChecker $authChecker,
        SluggerInterface $slugger,
        ActivityLogger $activityLogger
    ): Response {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            $this->addFlash('error', 'Acces non autorise.');
            return $this->redirectToRoute('app_home');
        }

        $contenu = new Contenu();
        $quizChoices = $this->buildQuizChoices(
            $quizRepository->findForContentForm(null, null),
            null
        );
        $form = $this->createForm(ContenuType::class, $contenu, [
            'types_data' => ['texte'],
            'resources_data' => [],
            'quiz_choices' => $quizChoices,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentUserId = $authChecker->getCurrentUser()?->getId();
            if ($this->hydrateResourcesFromForm($form, $contenu, $slugger, $em, $quizRepository, $currentUserId)) {
                $em->persist($contenu);
                $em->flush();
                $user = $authChecker->getCurrentUser();
                $activityLogger->log($user, 'content_create', 'contenu', (int) $contenu->getId(), [
                    'course_id' => $contenu->getCours()?->getId(),
                ]);

                $this->addFlash('success', 'Contenu cree.');
                $cours = $contenu->getCours();

                if ($cours) {
                    return $this->redirectToRoute('app_admin_cours_contents', ['id' => $cours->getId()]);
                }

                return $this->redirectToRoute('app_admin_modules_list');
            }
        }

        return $this->render('admin/contenu_new.html.twig', [
            'form' => $form->createView(),
            'contenu' => $contenu,
        ]);
    }

    #[Route('/admin/{id}/edit', name: 'app_admin_contenu_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        int $id,
        Request $request,
        ContenuRepository $contenuRepository,
        EntityManagerInterface $em,
        QuizRepository $quizRepository,
        AuthChecker $authChecker,
        SluggerInterface $slugger,
        ActivityLogger $activityLogger
    ): Response {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            $this->addFlash('error', 'Acces non autorise.');
            return $this->redirectToRoute('app_home');
        }

        $contenu = $contenuRepository->find($id);
        if (!$contenu) {
            $this->addFlash('error', 'Contenu introuvable.');
            return $this->redirectToRoute('app_admin_modules_list');
        }

        $form = $this->createForm(ContenuType::class, $contenu, [
            'types_data' => $contenu->getTypeContenuList(),
            'resources_data' => $contenu->getRessourcesForDisplay(),
            'quiz_choices' => $this->buildQuizChoices(
                $quizRepository->findForContentForm(null, $contenu->getCours()?->getId()),
                $contenu->getRessourcesForDisplay()['quiz_id'] ?? null
            ),
            'quiz_selected_data' => $contenu->getRessourcesForDisplay()['quiz_id'] ?? null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentUserId = $authChecker->getCurrentUser()?->getId();
            if ($this->hydrateResourcesFromForm($form, $contenu, $slugger, $em, $quizRepository, $currentUserId)) {
                $em->flush();
                $user = $authChecker->getCurrentUser();
                $activityLogger->log($user, 'content_edit', 'contenu', (int) $contenu->getId(), [
                    'course_id' => $contenu->getCours()?->getId(),
                ]);
                $this->addFlash('success', 'Contenu mis a jour.');
                $cours = $contenu->getCours();

                if ($cours) {
                    return $this->redirectToRoute('app_admin_cours_contents', ['id' => $cours->getId()]);
                }

                return $this->redirectToRoute('app_admin_modules_list');
            }
        }

        return $this->render('admin/contenu_edit.html.twig', [
            'form' => $form->createView(),
            'contenu' => $contenu,
        ]);
    }

    #[Route('/admin/{id}/delete', name: 'app_admin_contenu_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        int $id,
        Request $request,
        ContenuRepository $contenuRepository,
        EntityManagerInterface $em,
        AuthChecker $authChecker,
        ActivityLogger $activityLogger
    ): Response
    {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            $this->addFlash('error', 'Acces non autorise.');
            return $this->redirectToRoute('app_home');
        }

        $contenu = $contenuRepository->find($id);
        if ($contenu) {
            $this->cleanupUploadedResources($contenu->getRessourcesForDisplay());
            $deletedId = (int) $contenu->getId();
            $em->remove($contenu);
            $em->flush();
            $user = $authChecker->getCurrentUser();
            $activityLogger->log($user, 'content_delete', 'contenu', $deletedId);
            $this->addFlash('success', 'Contenu supprime.');
        }

        return $this->redirectToRoute('app_admin_modules_list');
    }

    private function hydrateResourcesFromForm(
        FormInterface $form,
        Contenu $contenu,
        SluggerInterface $slugger,
        EntityManagerInterface $em,
        QuizRepository $quizRepository,
        ?int $creatorId
    ): bool
    {
        $types = array_values(array_unique(array_filter((array) $form->get('typeContenu')->getData())));

        if ($types === []) {
            $form->get('typeContenu')->addError(new FormError('Selectionnez au moins un type de contenu.'));
            return false;
        }

        $existing = $contenu->getRessourcesForDisplay();
        $resources = [];

        $externalUrl = trim((string) $form->get('urlContenu')->getData());
        $externalUrl = $externalUrl !== '' ? $externalUrl : null;

        if ($externalUrl !== null && filter_var($externalUrl, FILTER_VALIDATE_URL) === false) {
            $form->get('urlContenu')->addError(new FormError('Le lien externe doit etre une URL valide.'));
            return false;
        }

        $textContent = trim((string) $form->get('texteContenu')->getData());
        if ($textContent !== '') {
            $resources['texte'] = $textContent;
        } elseif (isset($existing['texte']) && in_array('texte', $types, true)) {
            $resources['texte'] = $existing['texte'];
        }

        if (in_array('lien', $types, true)) {
            if ($externalUrl === null && !empty($existing['lien'])) {
                $resources['lien'] = $existing['lien'];
            } elseif ($externalUrl !== null) {
                $resources['lien'] = $externalUrl;
            } else {
                $form->get('urlContenu')->addError(new FormError('Un lien est requis quand le type Lien est selectionne.'));
                return false;
            }
        }

        if (in_array('video', $types, true)) {
            $videoPath = $this->handleUpload($form->get('videoFile')->getData(), $slugger, 'video');
            if ($videoPath !== null) {
                $resources['video_file'] = $videoPath;
            } elseif (!empty($existing['video_file'])) {
                $resources['video_file'] = $existing['video_file'];
            }

            if ($externalUrl !== null) {
                $resources['video_link'] = $externalUrl;
            } elseif (!empty($existing['video_link'])) {
                $resources['video_link'] = $existing['video_link'];
            }

            if (empty($resources['video_file']) && empty($resources['video_link'])) {
                $form->get('videoFile')->addError(new FormError('Ajoutez un fichier video ou un lien video.'));
                return false;
            }
        }

        if (in_array('pdf', $types, true)) {
            $pdfPath = $this->handleUpload($form->get('pdfFile')->getData(), $slugger, 'pdf');
            if ($pdfPath !== null) {
                $resources['pdf'] = $pdfPath;
            } elseif (!empty($existing['pdf'])) {
                $resources['pdf'] = $existing['pdf'];
            } else {
                $form->get('pdfFile')->addError(new FormError('Ajoutez un fichier PDF.'));
                return false;
            }
        }

        if (in_array('ppt', $types, true)) {
            $pptPath = $this->handleUpload($form->get('pptFile')->getData(), $slugger, 'ppt');
            if ($pptPath !== null) {
                $resources['ppt'] = $pptPath;
            } elseif (!empty($existing['ppt'])) {
                $resources['ppt'] = $existing['ppt'];
            } else {
                $form->get('pptFile')->addError(new FormError('Ajoutez un fichier PPT/PPTX.'));
                return false;
            }
        }

        if (in_array('texte', $types, true) && empty($resources['texte'])) {
            $form->get('texteContenu')->addError(new FormError('Saisissez un contenu texte.'));
            return false;
        }

        if (in_array('quiz', $types, true)) {
            $quizId = $form->get('quizExistingId')->getData();
            if (empty($quizId) && isset($existing['quiz_id'])) {
                $quizId = (int) $existing['quiz_id'];
            }

            if (empty($quizId)) {
                $form->get('quizExistingId')->addError(new FormError('Selectionnez un quiz existant depuis la gestion des quiz.'));
                return false;
            }

            $quiz = $quizRepository->find((int) $quizId);
            if (!$quiz) {
                $form->get('quizExistingId')->addError(new FormError('Quiz introuvable.'));
                return false;
            }

            if ($quiz->getIdCours() === null && $contenu->getCours()) {
                $quiz->setIdCours($contenu->getCours()->getId());
                $em->flush();
            }

            $resources['quiz_id'] = $quiz->getId();
            $resources['quiz_title'] = $quiz->getTitre();
        }

        $contenu->setTypeContenuFromArray($types);
        $contenu->setRessources($resources);
        $contenu->setUrlContenu($resources['lien'] ?? $resources['video_link'] ?? null);

        return true;
    }

    /**
     * @param Quiz[] $quizzes
     * @return array<string,int>
     */
    private function buildQuizChoices(array $quizzes, ?int $selectedId): array
    {
        $choices = [];
        foreach ($quizzes as $quiz) {
            $label = sprintf(
                '#%d - %s%s',
                (int) $quiz->getId(),
                (string) ($quiz->getTitre() ?? 'Sans titre'),
                $quiz->getIdCours() ? ' (Cours ' . $quiz->getIdCours() . ')' : ''
            );
            $choices[$label] = (int) $quiz->getId();
        }

        if ($selectedId !== null && $selectedId > 0 && !in_array($selectedId, $choices, true)) {
            $choices['#' . $selectedId . ' - Quiz associe'] = $selectedId;
        }

        return $choices;
    }

    private function handleUpload(?UploadedFile $file, SluggerInterface $slugger, string $prefix): ?string
    {
        if (!$file instanceof UploadedFile) {
            return null;
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/contenu';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $slugger->slug($originalName)->lower()->toString();
        $filename = sprintf('%s-%s-%s.%s', $prefix, $safeName, uniqid('', true), $file->guessExtension() ?: $file->getClientOriginalExtension());

        $file->move($uploadDir, $filename);

        return '/uploads/contenu/' . $filename;
    }

    /**
     * @param array<string,mixed> $resources
     */
    private function cleanupUploadedResources(array $resources): void
    {
        foreach (['pdf', 'ppt', 'video_file'] as $key) {
            $path = $resources[$key] ?? null;
            if (!is_string($path) || !str_starts_with($path, '/uploads/contenu/')) {
                continue;
            }
            $full = $this->getParameter('kernel.project_dir') . '/public' . $path;
            if (is_file($full)) {
                @unlink($full);
            }
        }
    }
}
