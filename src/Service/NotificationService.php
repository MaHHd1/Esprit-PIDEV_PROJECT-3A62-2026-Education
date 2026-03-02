<?php

namespace App\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class NotificationService
{
    public function __construct(private HubInterface $hub) {}

    // =========================================================
    //  QUIZ — Étudiant a passé un quiz → notif à l'enseignant
    // =========================================================
    public function notifierEnseignantQuizPasse(
        string $enseignantUsername,
        string $etudiantUsername,
        string $quizTitre,
        int    $quizId,
        int    $score,
        int    $scoreMax
    ): void {
        $topic = 'notifications/enseignant/' . str_replace(' ', '_', $enseignantUsername);

        $this->hub->publish(new Update(
            topics: [$topic],
            data: json_encode([
                'type'    => 'quiz_passe',
                'titre'   => "Quiz passé par {$etudiantUsername}",
                'message' => "{$etudiantUsername} a terminé « {$quizTitre} » avec un score de {$score}/{$scoreMax}.",
                'url'     => "/quiz/{$quizId}/resultats",
                'icon'    => 'bi-clipboard-check-fill',
                'color'   => 'success',
                'time'    => (new \DateTimeImmutable())->format('H:i'),
            ])
        ));
    }

    // =========================================================
    //  QUIZ — Enseignant crée un quiz → notif à tous les étudiants
    // =========================================================
    public function notifierEtudiantsNouveauQuiz(
        string $enseignantUsername,
        string $quizTitre,
        int    $quizId,
        string $niveau
    ): void {
        $this->hub->publish(new Update(
            topics: ['notifications/etudiants'],
            data: json_encode([
                'type'    => 'nouveau_quiz',
                'titre'   => 'Nouveau quiz disponible !',
                'message' => "{$enseignantUsername} a publié « {$quizTitre} » (niveau : {$niveau}).",
                'url'     => "/quiz/{$quizId}",
                'icon'    => 'bi-mortarboard-fill',
                'color'   => 'primary',
                'time'    => (new \DateTimeImmutable())->format('H:i'),
            ])
        ));
    }

    // =========================================================
    //  FORUM — Quelqu'un commente un forum → notif au créateur
    // =========================================================
    public function notifierCreateurForumCommentaire(
        string $createurUsername,
        string $auteurCommentaire,
        string $forumTitre,
        int    $forumId
    ): void {
        $topic = 'notifications/user/' . str_replace(' ', '_', $createurUsername);

        $this->hub->publish(new Update(
            topics: [$topic],
            data: json_encode([
                'type'    => 'forum_commentaire',
                'titre'   => 'Nouveau commentaire sur votre forum',
                'message' => "{$auteurCommentaire} a commenté votre forum « {$forumTitre} ».",
                'url'     => "/forums/{$forumId}",
                'icon'    => 'bi-chat-dots-fill',
                'color'   => 'primary',
                'time'    => (new \DateTimeImmutable())->format('H:i'),
            ])
        ));
    }

    // =========================================================
    //  FORUM — Nouveau forum créé → notif à tous
    // =========================================================
    public function notifierTousNouveauForum(
        string $createurUsername,
        string $forumTitre,
        int    $forumId
    ): void {
        $this->hub->publish(new Update(
            topics: ['notifications/tous'],
            data: json_encode([
                'type'    => 'nouveau_forum',
                'titre'   => 'Nouveau forum créé !',
                'message' => "{$createurUsername} a créé un nouveau forum « {$forumTitre} ».",
                'url'     => "/forums/{$forumId}",
                'icon'    => 'bi-chat-dots-fill',
                'color'   => 'warning',
                'time'    => (new \DateTimeImmutable())->format('H:i'),
            ])
        ));
    }
}