<?php

namespace App\Entity;

use App\Repository\CommentaireForumRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Utilisateur;
use App\Entity\ForumDiscussion;

#[ORM\Entity(repositoryClass: CommentaireForumRepository::class)]
#[ORM\Table(name: 'commentaire_forum')]
class CommentaireForum
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_commentaire')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ForumDiscussion::class)]
    #[ORM\JoinColumn(name: 'id_forum', referencedColumnName: 'id_forum', nullable: false, onDelete: 'CASCADE')]
    private ?ForumDiscussion $forum = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(type: 'text')]
    private string $contenu = '';

    #[ORM\Column(name: 'date_publication')]
    private \DateTimeImmutable $datePublication;

    #[ORM\Column(name: 'date_modification', nullable: true)]
    private ?\DateTimeImmutable $dateModification = null;

    #[ORM\ManyToOne(targetEntity: CommentaireForum::class)]
    #[ORM\JoinColumn(name: 'id_parent', referencedColumnName: 'id_commentaire', nullable: true, onDelete: 'SET NULL')]
    private ?CommentaireForum $parent = null;

    #[ORM\Column]
    private int $likes = 0;

    #[ORM\Column]
    private int $dislikes = 0;

    #[ORM\Column]
    private int $signalements = 0;

    // approuve | en_attente | supprime
    #[ORM\Column(length: 20)]
    private string $statut = 'approuve';

    #[ORM\Column(name: 'est_modifie')]
    private bool $estModifie = false;

    #[ORM\Column(name: 'nb_reponses')]
    private int $nbReponses = 0;

    public function __construct()
    {
        $this->datePublication = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getForum(): ?ForumDiscussion { return $this->forum; }
    public function setForum(ForumDiscussion $f): self { $this->forum = $f; return $this; }

    public function getUtilisateur(): ?Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(Utilisateur $u): self { $this->utilisateur = $u; return $this; }

    public function getContenu(): string { return $this->contenu; }
    public function setContenu(string $c): self { $this->contenu = $c; return $this; }

    public function getDatePublication(): \DateTimeImmutable { return $this->datePublication; }

    public function getDateModification(): ?\DateTimeImmutable { return $this->dateModification; }
    public function setDateModification(?\DateTimeImmutable $d): self { $this->dateModification = $d; return $this; }

    public function getParent(): ?CommentaireForum { return $this->parent; }
    public function setParent(?CommentaireForum $p): self { $this->parent = $p; return $this; }

    public function getLikes(): int { return $this->likes; }
    public function setLikes(int $l): self { $this->likes = $l; return $this; }

    public function getDislikes(): int { return $this->dislikes; }
    public function setDislikes(int $d): self { $this->dislikes = $d; return $this; }

    public function getSignalements(): int { return $this->signalements; }
    public function setSignalements(int $s): self { $this->signalements = $s; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $s): self { $this->statut = $s; return $this; }

    public function isEstModifie(): bool { return $this->estModifie; }
    public function setEstModifie(bool $b): self { $this->estModifie = $b; return $this; }

    public function getNbReponses(): int { return $this->nbReponses; }
    public function setNbReponses(int $n): self { $this->nbReponses = $n; return $this; }
}
