<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Index(columns: ['date_envoi'], name: 'idx_message_date_envoi')]
#[ORM\Index(columns: ['statut'], name: 'idx_message_statut')]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "messagesEnvoyes")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $expediteur = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "messagesRecus")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $destinataire = null;

    #[ORM\Column(length: 255)]
    private string $objet = '';

    #[ORM\Column(type: 'text')]
    private string $contenu = '';

    #[ORM\Column(name: 'date_envoi')]
    private \DateTimeImmutable $dateEnvoi;

    #[ORM\Column(name: 'date_lecture', nullable: true)]
    private ?\DateTimeImmutable $dateLecture = null;

    #[ORM\Column(length: 20)]
    private string $statut = 'envoye';

    #[ORM\Column(length: 20)]
    private string $priorite = 'normal';

    #[ORM\Column(name: 'piece_jointe_url', length: 255, nullable: true)]
    private ?string $pieceJointeUrl = null;

    #[ORM\ManyToOne(targetEntity: Message::class)]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Message $parent = null;

    #[ORM\Column(length: 20)]
    private string $categorie = 'personnel';

    #[ORM\Column(name: 'est_archive_expediteur')]
    private bool $estArchiveExpediteur = false;

    #[ORM\Column(name: 'est_archive_destinataire')]
    private bool $estArchiveDestinataire = false;

    #[ORM\Column(type: 'boolean')]
    private bool $estSupprimeExpediteur = false;

    #[ORM\Column(type: 'boolean')]
    private bool $estSupprimeDestinataire = false;

    public function __construct()
    {
        $this->dateEnvoi = new \DateTimeImmutable();
    }

    // Getters & Setters essentiels
    public function getId(): ?int { return $this->id; }

    public function getExpediteur(): ?Utilisateur { return $this->expediteur; }
    public function setExpediteur(?Utilisateur $u): self { $this->expediteur = $u; return $this; }

    public function getDestinataire(): ?Utilisateur { return $this->destinataire; }
    public function setDestinataire(?Utilisateur $u): self { $this->destinataire = $u; return $this; }

    public function getObjet(): string { return $this->objet; }
    public function setObjet(string $objet): self { $this->objet = $objet; return $this; }

    public function getContenu(): string { return $this->contenu; }
    public function setContenu(string $contenu): self { $this->contenu = $contenu; return $this; }

    public function getDateEnvoi(): \DateTimeImmutable { return $this->dateEnvoi; }
    public function setDateEnvoi(\DateTimeImmutable $d): self { $this->dateEnvoi = $d; return $this; }

    public function getDateLecture(): ?\DateTimeImmutable { return $this->dateLecture; }
    public function setDateLecture(?\DateTimeImmutable $d): self { $this->dateLecture = $d; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $s): self { $this->statut = $s; return $this; }

    public function getPriorite(): string { return $this->priorite; }
    public function setPriorite(string $p): self { $this->priorite = $p; return $this; }

    public function getPieceJointeUrl(): ?string { return $this->pieceJointeUrl; }
    public function setPieceJointeUrl(?string $p): self { $this->pieceJointeUrl = $p; return $this; }

    public function getParent(): ?Message { return $this->parent; }
    public function setParent(?Message $m): self { $this->parent = $m; return $this; }

    public function getCategorie(): string { return $this->categorie; }
    public function setCategorie(string $c): self { $this->categorie = $c; return $this; }

    public function isLu(): bool { return $this->statut === 'lu'; }
}
