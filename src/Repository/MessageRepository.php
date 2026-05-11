<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /* =========================
       INBOX
    ========================== */
    public function paginateInbox(Utilisateur $user, ?string $q, int $page, int $limit = 10): array
    {
        return $this->paginateBase('inbox', $user, $q, $page, $limit);
    }

    /* =========================
       SENT
    ========================== */
    public function paginateSent(Utilisateur $user, ?string $q, int $page, int $limit = 10): array
    {
        return $this->paginateBase('sent', $user, $q, $page, $limit);
    }

    /* =========================
       ARCHIVE
    ========================== */
    public function paginateArchive(Utilisateur $user, ?string $q, int $page, int $limit = 10): array
    {
        return $this->paginateBase('archive', $user, $q, $page, $limit);
    }

    /* =========================
       BASE PAGINATION + SEARCH
    ========================== */
    private function paginateBase(string $mode, Utilisateur $user, ?string $q, int $page, int $limit): array
    {
        $page  = max(1, $page);
        $limit = max(1, min(50, $limit));
        $q     = $q ? trim($q) : '';

        $qb = $this->createQueryBuilder('m');

        if ($mode === 'inbox') {
            $qb->andWhere('m.destinataire = :u')
               ->setParameter('u', $user)
               ->andWhere('(m.estSupprimeDestinataire = false OR m.estSupprimeDestinataire IS NULL)')
               ->andWhere('(m.estArchiveDestinataire = false OR m.estArchiveDestinataire IS NULL)');
        } elseif ($mode === 'sent') {
            $qb->andWhere('m.expediteur = :u')
               ->setParameter('u', $user)
               ->andWhere('(m.estSupprimeExpediteur = false OR m.estSupprimeExpediteur IS NULL)')
               ->andWhere('(m.estArchiveExpediteur = false OR m.estArchiveExpediteur IS NULL)');
        } else {
            $qb->andWhere('(
                (m.destinataire = :u
                    AND m.estArchiveDestinataire = true
                    AND (m.estSupprimeDestinataire = false OR m.estSupprimeDestinataire IS NULL)
                )
                OR
                (m.expediteur = :u
                    AND m.estArchiveExpediteur = true
                    AND (m.estSupprimeExpediteur = false OR m.estSupprimeExpediteur IS NULL)
                )
            )')
            ->setParameter('u', $user);
        }

        if ($q !== '') {
            $qb->andWhere('LOWER(m.objet) LIKE :q OR LOWER(m.contenu) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $qb->orderBy('m.dateEnvoi', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $paginator = new Paginator($qb);
        $total     = count($paginator);
        $pages     = (int) ceil($total / $limit);

        return [
            'items' => iterator_to_array($paginator),
            'total' => $total,
            'page'  => $page,
            'pages' => max(1, $pages),
            'limit' => $limit,
            'q'     => $q,
        ];
    }

    /* =========================
       ADMIN — TOUS LES MESSAGES
    ========================== */
    public function paginateAll(?string $q, int $page, int $limit = 15): array
    {
        $page  = max(1, $page);
        $limit = max(1, min(50, $limit));
        $q     = $q ? trim($q) : '';

        $qb = $this->createQueryBuilder('m');

        if ($q !== '') {
            $qb->andWhere('LOWER(m.objet) LIKE :q OR LOWER(m.contenu) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $qb->orderBy('m.dateEnvoi', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $paginator = new Paginator($qb);
        $total     = count($paginator);
        $pages     = (int) ceil($total / $limit);

        return [
            'items' => iterator_to_array($paginator),
            'total' => $total,
            'page'  => $page,
            'pages' => max(1, $pages),
            'limit' => $limit,
            'q'     => $q,
        ];
    }

    /* =========================
       THREAD (Conversation)
    ========================== */
    public function getThread(Message $root): array
    {
        $rootMsg = $root;
        while ($rootMsg->getParent() !== null) {
            $rootMsg = $rootMsg->getParent();
        }

        $messages = $this->createQueryBuilder('m')
            ->andWhere('m = :root OR m.parent = :root')
            ->setParameter('root', $rootMsg)
            ->orderBy('m.dateEnvoi', 'ASC')
            ->getQuery()
            ->getResult();

        return [$rootMsg, $messages];
    }
    public function findConversation(Utilisateur $user1, Utilisateur $user2): array
{
    return $this->createQueryBuilder('m')
        ->where(
            '(m.expediteur = :user1 AND m.destinataire = :user2) OR 
             (m.expediteur = :user2 AND m.destinataire = :user1)'
        )
        ->setParameter('user1', $user1)
        ->setParameter('user2', $user2)
        ->andWhere('m.estSupprimeExpediteur = false')
        ->andWhere('m.estSupprimeDestinataire = false')
        ->orderBy('m.dateEnvoi', 'ASC')
        ->setMaxResults(20) // 20 derniers pour ne pas dépasser le contexte Groq
        ->getQuery()
        ->getResult();
}
}