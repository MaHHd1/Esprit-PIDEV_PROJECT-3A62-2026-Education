<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Entity\Enseignant;
use App\Entity\CommentaireForum;
use App\Form\CommentaireForumType;
use App\Repository\CommentaireForumRepository;
use App\Repository\ForumDiscussionRepository;
use App\Service\AuthChecker;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/comments')]
class CommentaireController extends AbstractController
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

    #[Route('/new/{forumId}', name: 'comment_new')]
    public function new(
        int $forumId,
        Request $request,
        ForumDiscussionRepository $forumRepo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user  = $this->getActiveUser();
        $forum = $forumRepo->find($forumId);

        if (!$forum) {
            throw $this->createNotFoundException("Forum introuvable.");
        }

        $comment = new CommentaireForum();
        $comment->setForum($forum);
        $comment->setUtilisateur($user);

        $form = $this->createForm(CommentaireForumType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forum->setDerniereActivite(new \DateTimeImmutable());
            $em->persist($comment);
            $em->flush();

            // ✅ NOTIFICATION — notifier le créateur du forum
            // (seulement si ce n'est pas lui-même qui commente)
            try {
                $createur = $forum->getCreateur();
                if ($createur && $createur->getId() !== $user->getId()) {
                    $this->notificationService->notifierCreateurForumCommentaire(
                        createurUsername:  $createur->getUsername(),
                        auteurCommentaire: $user->getUsername(),
                        forumTitre:        $forum->getTitre(),
                        forumId:           $forum->getId()
                    );
                }
            } catch (\Exception $e) {
                // Ne pas bloquer si Mercure est down
            }

            return $this->redirectToRoute('forum_show', ['id' => $forum->getId()]);
        }

        return $this->render('comment/new.html.twig', array_merge(
            $this->buildUserVars($user),
            [
                'form'  => $form->createView(),
                'forum' => $forum,
            ]
        ));
    }

    #[Route('/reply/{commentId}', name: 'comment_reply')]
    public function reply(
        int $commentId,
        Request $request,
        CommentaireForumRepository $commentRepo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user   = $this->getActiveUser();
        $parent = $commentRepo->find($commentId);

        if (!$parent) {
            throw $this->createNotFoundException("Commentaire introuvable.");
        }

        $reply = new CommentaireForum();
        $reply->setForum($parent->getForum());
        $reply->setUtilisateur($user);
        $reply->setParent($parent);

        $form = $this->createForm(CommentaireForumType::class, $reply);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $parent->setNbReponses($parent->getNbReponses() + 1);
            $parent->getForum()->setDerniereActivite(new \DateTimeImmutable());
            $em->persist($reply);
            $em->flush();

            // ✅ NOTIFICATION — notifier le créateur du forum
            // (seulement si ce n'est pas lui-même qui répond)
            try {
                $forum    = $parent->getForum();
                $createur = $forum->getCreateur();
                if ($createur && $createur->getId() !== $user->getId()) {
                    $this->notificationService->notifierCreateurForumCommentaire(
                        createurUsername:  $createur->getUsername(),
                        auteurCommentaire: $user->getUsername(),
                        forumTitre:        $forum->getTitre(),
                        forumId:           $forum->getId()
                    );
                }
            } catch (\Exception $e) {
                // Ne pas bloquer si Mercure est down
            }

            return $this->redirectToRoute('forum_show', ['id' => $parent->getForum()->getId()]);
        }

        return $this->render('comment/reply.html.twig', array_merge(
            $this->buildUserVars($user),
            [
                'form'   => $form->createView(),
                'parent' => $parent,
            ]
        ));
    }

    // =========================================================
    //  ROUTES ADMIN
    // =========================================================

    #[Route('/admin/comments/{id}/delete', name: 'admin_comment_delete', methods: ['POST'])]
    public function adminDeleteComment(int $id, CommentaireForumRepository $repo, EntityManagerInterface $em): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->authChecker->isAdmin()) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs.');
        }

        $c = $repo->find($id);
        if (!$c) throw $this->createNotFoundException();

        $em->remove($c);
        $em->flush();

        $this->addFlash('success', 'Commentaire supprimé par l\'administrateur.');
        return $this->redirectToRoute('admin_forum_list');
    }

    #[Route('/{id}/like', name: 'comment_like', methods: ['POST'])]
    public function like(int $id, CommentaireForumRepository $repo, EntityManagerInterface $em): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $c = $repo->find($id);
        if (!$c) throw $this->createNotFoundException();

        $c->setLikes($c->getLikes() + 1);
        $c->getForum()?->setDerniereActivite(new \DateTimeImmutable());
        $em->flush();

        return $this->redirectToRoute('forum_show', ['id' => $c->getForum()->getId()]);
    }

    #[Route('/{id}/dislike', name: 'comment_dislike', methods: ['POST'])]
    public function dislike(int $id, CommentaireForumRepository $repo, EntityManagerInterface $em): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $c = $repo->find($id);
        if (!$c) throw $this->createNotFoundException();

        $c->setDislikes($c->getDislikes() + 1);
        $c->getForum()?->setDerniereActivite(new \DateTimeImmutable());
        $em->flush();

        return $this->redirectToRoute('forum_show', ['id' => $c->getForum()->getId()]);
    }

    #[Route('/{id}/report', name: 'comment_report', methods: ['POST'])]
    public function report(int $id, CommentaireForumRepository $repo, EntityManagerInterface $em): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $c = $repo->find($id);
        if (!$c) throw $this->createNotFoundException();

        $c->setSignalements($c->getSignalements() + 1);
        if ($c->getSignalements() >= 3 && $c->getStatut() !== 'supprime') {
            $c->setStatut('en_attente');
        }
        $c->getForum()?->setDerniereActivite(new \DateTimeImmutable());
        $em->flush();

        return $this->redirectToRoute('forum_show', ['id' => $c->getForum()->getId()]);
    }

    #[Route('/{id}/delete', name: 'comment_delete', methods: ['POST'])]
    public function delete(int $id, CommentaireForumRepository $repo, EntityManagerInterface $em): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $c = $repo->find($id);
        if (!$c) throw $this->createNotFoundException();

        $c->setStatut('supprime');
        $c->getForum()?->setDerniereActivite(new \DateTimeImmutable());
        $em->flush();

        return $this->redirectToRoute('forum_show', ['id' => $c->getForum()->getId()]);
    }
}
