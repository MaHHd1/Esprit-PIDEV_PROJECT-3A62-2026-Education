<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Entity\Enseignant;
use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\MessageRepository;
use App\Service\AuthChecker;
use App\Service\GroqService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[Route('/messages')]
class MessageController extends AbstractController
{
    public function __construct(
        private AuthChecker $authChecker,
        private GroqService $groq           // ← Groq injecté ici
    ) {}

    // =========================================================
    //  HELPER — Variables utilisateur pour les templates Twig
    // =========================================================

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

    // =========================================================
    //  BOÎTE DE RÉCEPTION
    // =========================================================

    #[Route('/inbox', name: 'message_inbox')]
    public function inbox(Request $request, MessageRepository $repo): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->authChecker->getCurrentUser();
        $page = $request->query->getInt('page', 1);
        $q    = $request->query->get('q', '');
        $data = $repo->paginateInbox($user, $q, $page, 10);

        return $this->render('message/inbox.html.twig', array_merge(
            $this->buildUserVars($user),
            [
                'messages' => $data['items'],
                'total'    => $data['total'],
                'page'     => $data['page'],
                'pages'    => $data['pages'],
                'q'        => $data['q'],
            ]
        ));
    }

    // =========================================================
    //  MESSAGES ENVOYÉS
    // =========================================================

    #[Route('/sent', name: 'message_sent')]
    public function sent(Request $request, MessageRepository $repo): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->authChecker->getCurrentUser();
        $page = $request->query->getInt('page', 1);
        $q    = $request->query->get('q', '');
        $data = $repo->paginateSent($user, $q, $page, 10);

        return $this->render('message/sent.html.twig', array_merge(
            $this->buildUserVars($user),
            [
                'messages' => $data['items'],
                'total'    => $data['total'],
                'page'     => $data['page'],
                'pages'    => $data['pages'],
                'q'        => $data['q'],
            ]
        ));
    }

    // =========================================================
    //  ARCHIVE
    // =========================================================

    #[Route('/archive', name: 'message_archive')]
    public function archive(Request $request, MessageRepository $repo): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->authChecker->getCurrentUser();
        $page = $request->query->getInt('page', 1);
        $q    = $request->query->get('q', '');
        $data = $repo->paginateArchive($user, $q, $page, 10);

        return $this->render('message/archive.html.twig', array_merge(
            $this->buildUserVars($user),
            [
                'messages' => $data['items'],
                'total'    => $data['total'],
                'page'     => $data['page'],
                'pages'    => $data['pages'],
                'q'        => $data['q'],
            ]
        ));
    }

    // =========================================================
    //  NOUVEAU MESSAGE — avec modération IA (🚫 Groq)
    // =========================================================

    #[Route('/new', name: 'message_new')]
    public function new(Request $request, EntityManagerInterface $em, HubInterface $hub): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->authChecker->getCurrentUser();

        $message = new Message();
        $message->setExpediteur($user);

        $form = $this->createForm(MessageType::class, $message, [
            'active_user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ─────────────────────────────────────────────────
            //  🚫 MODÉRATION IA — Analyse avant envoi
            // ─────────────────────────────────────────────────
            try {
                $analysis = $this->groq->moderateMessage(
                    $message->getObjet(),
                    $message->getContenu()
                );

                  if ($analysis['action'] === 'block') {
    $this->addFlash('danger', '🚫 Message bloqué');
    return $this->render('message/new.html.twig', array_merge(
        $this->buildUserVars($user),
        [
            'form'    => $form->createView(),
            'blocked' => true,  // ← ajouter ça
        ]
    ));
}
                if ($analysis['action'] === 'warn') {
                    $this->addFlash('warning', '⚠️ Attention : ' . ($analysis['reason'] ?? 'Message signalé, vérifiez votre contenu.'));
                    // On laisse passer avec avertissement
                }
            } catch (\Throwable $e) {
                // Ne jamais bloquer l'envoi si Groq est indisponible
                // On logue silencieusement et on continue
            }
            // ─────────────────────────────────────────────────

            $message->setDateEnvoi(new \DateTimeImmutable());
            $message->setStatut('envoye');

            $em->persist($message);
            $em->flush();

            $this->publishNotification($hub, $message);

            $this->addFlash('success', 'Message envoyé avec succès !');
            return $this->redirectToRoute('message_inbox');
        }

        return $this->render('message/new.html.twig', array_merge(
            $this->buildUserVars($user),
            ['form' => $form->createView()]
        ));
    }

    // =========================================================
    //  AFFICHER UN MESSAGE
    // =========================================================

    #[Route('/{id}', name: 'message_show', requirements: ['id' => '\d+'])]
    public function show(int $id, MessageRepository $repo, EntityManagerInterface $em): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->authChecker->getCurrentUser();

        $msg = $repo->find($id);
        if (!$msg) {
            throw $this->createNotFoundException('Message introuvable.');
        }

        // Marquer comme lu
        if ($msg->getDestinataire()?->getId() === $user->getId() && $msg->getDateLecture() === null) {
            $msg->setDateLecture(new \DateTimeImmutable());
            $msg->setStatut('lu');
            $em->flush();
        }

        [$root, $thread] = $repo->getThread($msg);

        return $this->render('message/show.html.twig', array_merge(
            $this->buildUserVars($user),
            [
                'root'    => $root,
                'thread'  => $thread,
                'message' => $msg,
            ]
        ));
    }

    // =========================================================
    //  RÉPONDRE — avec modération IA (🚫 Groq)
    // =========================================================

    #[Route('/{id}/reply', name: 'message_reply', requirements: ['id' => '\d+'])]
    public function reply(int $id, Request $request, MessageRepository $repo, EntityManagerInterface $em, HubInterface $hub): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->authChecker->getCurrentUser();

        $parent = $repo->find($id);
        if (!$parent) {
            throw $this->createNotFoundException('Message introuvable.');
        }

        $reply = new Message();
        $reply->setExpediteur($user);

        $dest = ($parent->getExpediteur()?->getId() === $user->getId())
            ? $parent->getDestinataire()
            : $parent->getExpediteur();

        $reply->setDestinataire($dest);
        $reply->setObjet('Re: ' . $parent->getObjet());
        $reply->setParent($parent);

        $form = $this->createForm(MessageType::class, $reply, [
            'active_user' => $user,
            'reply_mode'  => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ─────────────────────────────────────────────────
            //  🚫 MODÉRATION IA — Analyse avant envoi
            // ─────────────────────────────────────────────────
            try {
                $analysis = $this->groq->moderateMessage(
                    $reply->getObjet(),
                    $reply->getContenu()
                );

                if ($analysis['action'] === 'block') {
    $this->addFlash('danger', '🚫 Message bloqué');
    return $this->render('message/new.html.twig', array_merge(
        $this->buildUserVars($user),
        [
            'form'    => $form->createView(),
            'blocked' => true,  // ← ajouter ça
        ]
    ));
}

                if ($analysis['action'] === 'warn') {
                    $this->addFlash('warning', '⚠️ Attention : ' . ($analysis['reason'] ?? 'Message signalé.'));
                }
            } catch (\Throwable $e) {
                // Groq indisponible → on ne bloque pas l'envoi
            }
            // ─────────────────────────────────────────────────

            $reply->setDateEnvoi(new \DateTimeImmutable());
            $reply->setStatut('envoye');

            $em->persist($reply);
            $em->flush();

            $this->publishNotification($hub, $reply);

            return $this->redirectToRoute('message_show', ['id' => $parent->getId()]);
        }

        return $this->render('message/reply.html.twig', array_merge(
            $this->buildUserVars($user),
            [
                'form'   => $form->createView(),
                'parent' => $parent,
            ]
        ));
    }

    // =========================================================
    //  💡 SUGGESTION DE RÉPONSE — AJAX (Groq)
    // =========================================================

    #[Route('/{id}/suggest-reply', name: 'message_suggest_reply', methods: ['GET'], requirements: ['id' => '\d+'])]
public function suggestReply(int $id, MessageRepository $repo): JsonResponse
{
    if (!$this->authChecker->isLoggedIn()) {
        return $this->json(['error' => 'Non autorisé'], 401);
    }

    $user = $this->authChecker->getCurrentUser();

    $msg = $repo->find($id);
    if (!$msg) {
        return $this->json(['error' => 'Message introuvable'], 404);
    }

    try {
        // Récupérer l'autre utilisateur de la conversation
        $autreUser = ($msg->getExpediteur()?->getId() === $user->getId())
            ? $msg->getDestinataire()
            : $msg->getExpediteur();

        // Récupérer l'historique entre les deux
        $historique = $autreUser
            ? $repo->findConversation($user, $autreUser)
            : [];

        $suggestion = $this->groq->suggestReply(
            $msg->getObjet(),
            $msg->getContenu(),
            $historique  // ← historique passé ici
        );

        return $this->json(['suggestion' => $suggestion]);

    } catch (\Throwable $e) {
        return $this->json([
            'error' => 'Service IA indisponible',
            'debug' => $e->getMessage(),
        ], 503);
    }
}

    // =========================================================
    //  📝 RÉSUMÉ DU THREAD — AJAX, Admin uniquement (Groq)
    // =========================================================

    #[Route('/{id}/summary', name: 'message_thread_summary', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function threadSummary(int $id, MessageRepository $repo): JsonResponse
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->json(['error' => 'Non autorisé'], 401);
        }

        if (!$this->authChecker->isAdmin()) {
            return $this->json(['error' => 'Accès réservé aux administrateurs'], 403);
        }

        $msg = $repo->find($id);
        if (!$msg) {
            return $this->json(['error' => 'Message introuvable'], 404);
        }

        try {
            [, $thread] = $repo->getThread($msg);  // getThread() déjà dans ton repo
            $summary    = $this->groq->summarizeThread($thread);
            return $this->json(['summary' => $summary]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Service IA indisponible'], 503);
        }
    }

    // =========================================================
    //  ARCHIVER / DÉSARCHIVER
    // =========================================================

    #[Route('/{id}/toggle-archive', name: 'message_toggle_archive', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleArchive(int $id, MessageRepository $repo, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->authChecker->getCurrentUser();

        $msg = $repo->find($id);
        if (!$msg) {
            throw $this->createNotFoundException('Message introuvable.');
        }

        if ($msg->getDestinataire()?->getId() === $user->getId()) {
            $msg->setEstArchiveDestinataire(!$msg->isEstArchiveDestinataire());
        }
        if ($msg->getExpediteur()?->getId() === $user->getId()) {
            $msg->setEstArchiveExpediteur(!$msg->isEstArchiveExpediteur());
        }

        $em->flush();

        $from  = $request->request->get('from', 'inbox');
        $route = match ($from) {
            'sent'    => 'message_sent',
            'archive' => 'message_archive',
            default   => 'message_inbox',
        };

        return $this->redirectToRoute($route);
    }

    // =========================================================
    //  SUPPRIMER
    // =========================================================

    #[Route('/{id}/delete', name: 'message_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, MessageRepository $repo, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->authChecker->getCurrentUser();

        $msg = $repo->find($id);
        if (!$msg) {
            throw $this->createNotFoundException('Message introuvable.');
        }

        if ($msg->getDestinataire()?->getId() === $user->getId()) {
            $msg->setEstSupprimeDestinataire(true);
        }
        if ($msg->getExpediteur()?->getId() === $user->getId()) {
            $msg->setEstSupprimeExpediteur(true);
        }

        // Suppression physique si les deux ont supprimé
        if ($msg->isEstSupprimeExpediteur() && $msg->isEstSupprimeDestinataire()) {
            $em->remove($msg);
        }

        $em->flush();

        $from  = $request->request->get('from', 'inbox');
        $route = match ($from) {
            'sent'    => 'message_sent',
            'archive' => 'message_archive',
            default   => 'message_inbox',
        };

        return $this->redirectToRoute($route);
    }

    // =========================================================
    //  ROUTES ADMIN
    // =========================================================

    #[Route('/admin/messages', name: 'admin_message_list')]
    public function adminMessages(Request $request, MessageRepository $repo): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->authChecker->isAdmin()) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs.');
        }

        $user = $this->authChecker->getCurrentUser();
        $page = $request->query->getInt('page', 1);
        $q    = $request->query->get('q', '');
        $data = $repo->paginateAll($q, $page, 15);

        return $this->render('admin/messages.html.twig', array_merge(
            $this->buildUserVars($user),
            [
                'messages' => $data['items'],
                'total'    => $data['total'],
                'page'     => $data['page'],
                'pages'    => $data['pages'],
                'q'        => $data['q'],
            ]
        ));
    }

    #[Route('/admin/messages/{id}/delete', name: 'admin_message_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function adminDeleteMessage(int $id, MessageRepository $repo, EntityManagerInterface $em): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->authChecker->isAdmin()) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs.');
        }

        $msg = $repo->find($id);
        if (!$msg) {
            throw $this->createNotFoundException('Message introuvable.');
        }

        $em->remove($msg);
        $em->flush();

        $this->addFlash('success', 'Message supprimé par l\'administrateur.');
        return $this->redirectToRoute('admin_message_list');
    }

    // =========================================================
    //  HELPER PRIVÉ — Publication Mercure
    // =========================================================

    /**
     * Publie une notification temps réel au destinataire via Mercure.
     * Topic : /user/{id}/messages  → chaque user écoute son propre topic.
     */
    private function publishNotification(HubInterface $hub, Message $message): void
    {
        $destinataire = $message->getDestinataire();
        if (!$destinataire) return;

        $update = new Update(
            sprintf('/user/%d/messages', $destinataire->getId()),
            json_encode([
                'type'       => 'nouveau_message',
                'messageId'  => $message->getId(),
                'objet'      => $message->getObjet(),
                'expediteur' => $message->getExpediteur()?->getNomComplet() ?? 'Inconnu',
                'dateEnvoi'  => $message->getDateEnvoi()->format('d/m/Y H:i'),
                'priorite'   => $message->getPriorite(),
            ])
        );

        try {
            $hub->publish($update);
        } catch (\Throwable $e) {
            // Ne pas bloquer l'envoi si Mercure est indisponible
        }
    }
}
