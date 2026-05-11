<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Entity\Enseignant;
use App\Entity\ForumDiscussion;
use App\Form\ForumDiscussionType;
use App\Repository\CommentaireForumRepository;
use App\Repository\ForumDiscussionRepository;
use App\Service\AuthChecker;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/forums')]
class ForumController extends AbstractController
{
    private AuthChecker $authChecker;

    public function __construct(
        AuthChecker $authChecker,
        private NotificationService $notificationService  // ✅ AJOUTÉ
    ) {
        $this->authChecker = $authChecker;
    }

    private function getActiveUser()
    {
        if (!$this->authChecker->isLoggedIn()) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
        return $this->authChecker->getCurrentUser();
    }

    private function buildUserVars(object $user): array
    {
        if ($user instanceof Etudiant) {
            return ['activeUser' => $user, 'student' => $user];
        }
        if ($user instanceof Enseignant) {
            return ['activeUser' => $user, 'enseignant' => $user];
        }
        return ['activeUser' => $user];
    }

    private function getTemplate(object $user, string $name): string
    {
        $type = $user instanceof Enseignant ? 'enseignant' : 'etudiant';
        return "forum/_{$name}_{$type}.html.twig";
    }

    // =========================================================
    //  ROUTES UTILISATEUR
    // =========================================================

    #[Route('', name: 'forum_list')]
    public function list(Request $request, ForumDiscussionRepository $repo): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getActiveUser();
        $page = $request->query->getInt('page', 1);
        $q    = $request->query->get('q', '');
        $data = $repo->paginate($q, $page, 6);

        return $this->render($this->getTemplate($user, 'list'), array_merge(
            $this->buildUserVars($user),
            [
                'forums' => $data['items'],
                'total'  => $data['total'],
                'page'   => $data['page'],
                'pages'  => $data['pages'],
                'q'      => $data['q'],
            ]
        ));
    }

    #[Route('/new', name: 'forum_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user  = $this->getActiveUser();
        $forum = new ForumDiscussion();
        $forum->setCreateur($user);

        $form = $this->createForm(ForumDiscussionType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forum->setDerniereActivite(new \DateTimeImmutable());
            $em->persist($forum);
            $em->flush();

            // ✅ NOTIFICATION — Nouveau forum → notif à tous
            try {
                $this->notificationService->notifierTousNouveauForum(
                    createurUsername: $user->getUsername(),
                    forumTitre:       $forum->getTitre(),
                    forumId:          $forum->getId()
                );
            } catch (\Exception $e) {
                // Ne pas bloquer si Mercure est down
            }

            $this->addFlash('success', 'Forum créé avec succès.');
            return $this->redirectToRoute('forum_list');
        }

        return $this->render($this->getTemplate($user, 'new'), array_merge(
            $this->buildUserVars($user),
            ['form' => $form->createView()]
        ));
    }

    #[Route('/{id}', name: 'forum_show', requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        ForumDiscussionRepository $repo,
        CommentaireForumRepository $commentRepo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $forum = $repo->find($id);
        if (!$forum) {
            throw $this->createNotFoundException('Forum introuvable.');
        }

        $user = $this->getActiveUser();
        $forum->incrementVues();
        $em->flush();

        $comments = $commentRepo->byForumVisible($forum);

        return $this->render($this->getTemplate($user, 'show'), array_merge(
            $this->buildUserVars($user),
            [
                'forum'    => $forum,
                'comments' => $comments,
            ]
        ));
    }

    #[Route('/{id}/edit', name: 'forum_edit', requirements: ['id' => '\d+'])]
    public function edit(
        int $id,
        Request $request,
        ForumDiscussionRepository $repo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $forum = $repo->find($id);
        if (!$forum) {
            throw $this->createNotFoundException('Forum introuvable.');
        }

        $user = $this->getActiveUser();

        if ($forum->getCreateur() !== $user && !$this->authChecker->isAdmin()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à modifier ce forum.');
            return $this->redirectToRoute('forum_list');
        }

        $form = $this->createForm(ForumDiscussionType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forum->setEstModifie(true);
            $forum->setDateModification(new \DateTimeImmutable());
            $forum->setDerniereActivite(new \DateTimeImmutable());
            $em->flush();

            $this->addFlash('success', 'Forum modifié avec succès.');
            return $this->redirectToRoute('forum_show', ['id' => $forum->getId()]);
        }

        return $this->render($this->getTemplate($user, 'edit'), array_merge(
            $this->buildUserVars($user),
            [
                'form'  => $form->createView(),
                'forum' => $forum,
            ]
        ));
    }

    #[Route('/{id}/delete', name: 'forum_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        int $id,
        ForumDiscussionRepository $repo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $forum = $repo->find($id);
        if (!$forum) {
            throw $this->createNotFoundException('Forum introuvable.');
        }

        $user = $this->getActiveUser();

        if ($forum->getCreateur() !== $user && !$this->authChecker->isAdmin()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer ce forum.');
            return $this->redirectToRoute('forum_list');
        }

        $em->remove($forum);
        $em->flush();

        $this->addFlash('success', 'Forum supprimé avec succès.');
        return $this->redirectToRoute('forum_list');
    }

    #[Route('/{id}/like', name: 'forum_like', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function like(
        int $id,
        ForumDiscussionRepository $repo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $forum = $repo->find($id);
        if (!$forum) throw $this->createNotFoundException();

        $forum->setLikes(($forum->getLikes() ?? 0) + 1);
        $em->flush();

        return $this->redirectToRoute('forum_show', ['id' => $forum->getId()]);
    }

    #[Route('/{id}/dislike', name: 'forum_dislike', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function dislike(
        int $id,
        ForumDiscussionRepository $repo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $forum = $repo->find($id);
        if (!$forum) throw $this->createNotFoundException();

        $forum->setDislikes(($forum->getDislikes() ?? 0) + 1);
        $em->flush();

        return $this->redirectToRoute('forum_show', ['id' => $forum->getId()]);
    }

    #[Route('/{id}/report', name: 'forum_report', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function report(
        int $id,
        ForumDiscussionRepository $repo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $forum = $repo->find($id);
        if (!$forum) throw $this->createNotFoundException();

        $forum->setSignalements(($forum->getSignalements() ?? 0) + 1);
        $em->flush();

        return $this->redirectToRoute('forum_show', ['id' => $forum->getId()]);
    }

    // =========================================================
    //  ROUTES ADMIN
    // =========================================================

    #[Route('/admin/forums', name: 'admin_forum_list')]
    public function adminForums(
        Request $request,
        ForumDiscussionRepository $repo,
        CommentaireForumRepository $commentRepo
    ): Response {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->authChecker->isAdmin()) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs.');
        }

        $user = $this->getActiveUser();
        $page = $request->query->getInt('page', 1);
        $q    = $request->query->get('q', '');

        $data = $repo->paginate($q, $page, 15);
        $commentsByForum = $commentRepo->allGroupedByForum($data['items']);

        return $this->render('admin/forums.html.twig', array_merge(
            $this->buildUserVars($user),
            [
                'forums'          => $data['items'],
                'total'           => $data['total'],
                'page'            => $data['page'],
                'pages'           => $data['pages'],
                'q'               => $data['q'],
                'commentsByForum' => $commentsByForum,
            ]
        ));
    }

    #[Route('/admin/forums/{id}/delete', name: 'admin_forum_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function adminDeleteForum(
        int $id,
        ForumDiscussionRepository $repo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->authChecker->isAdmin()) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs.');
        }

        $forum = $repo->find($id);
        if (!$forum) {
            throw $this->createNotFoundException('Forum introuvable.');
        }

        $em->remove($forum);
        $em->flush();

        $this->addFlash('success', 'Forum supprimé par l\'administrateur.');
        return $this->redirectToRoute('admin_forum_list');
    }
}