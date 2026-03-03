<?php

namespace App\Entity;

use App\Repository\ForumDiscussionRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ForumDiscussionRepository::class)]
#[ORM\Table(name: 'forum_discussion')]
#[Vich\Uploadable]
class ForumDiscussion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_forum')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $titre = '';

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'id_createur', referencedColumnName: 'id', nullable: false)]
    private ?Utilisateur $createur = null;

    #[ORM\Column(name: 'id_cours', nullable: true)]
    private ?int $idCours = null;

    #[ORM\Column(name: 'date_creation')]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(length: 20)]
    private string $type = 'public';

    #[ORM\Column(length: 20)]
    private string $statut = 'ouvert';

    #[ORM\Column(name: 'nombre_vues')]
    private int $nombreVues = 0;

    #[ORM\Column(name: 'derniere_activite', nullable: true)]
    private ?\DateTimeImmutable $derniereActivite = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $tags = [];

    #[ORM\Column(name: 'regles_moderation', type: 'text', nullable: true)]
    private ?string $reglesModeration = null;

    // ✅ Champ image couverture — stocke le nom du fichier en BDD
    #[ORM\Column(name: 'image_couverture_url', length: 255, nullable: true)]
    private ?string $imageCouvertureUrl = null;

    // ✅ Champ virtuel VichUploader — pas en BDD
    #[Vich\UploadableField(mapping: 'forum_image', fileNameProperty: 'imageCouvertureUrl')]
    private ?File $imageCouvertureFile = null;

    // ✅ Pièce jointe — stocke le nom du fichier en BDD
    #[ORM\Column(name: 'piece_jointe_url', length: 255, nullable: true)]
    private ?string $pieceJointeUrl = null;

    // ✅ Champ virtuel VichUploader — pas en BDD
    #[Vich\UploadableField(mapping: 'forum_attachment', fileNameProperty: 'pieceJointeUrl')]
    private ?File $pieceJointeFile = null;

    #[ORM\Column(type: 'integer')]
    private int $likes = 0;

    #[ORM\Column(type: 'integer')]
    private int $dislikes = 0;

    #[ORM\Column(type: 'integer')]
    private int $signalements = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $estModifie = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateModification = null;

    public function __construct()
    {
        $this->dateCreation     = new \DateTimeImmutable();
        $this->derniereActivite = new \DateTimeImmutable();
    }

    // ============================
    // Getters & Setters existants
    // ============================

    public function getId(): ?int { return $this->id; }

    public function getTitre(): string { return $this->titre; }
    public function setTitre(string $titre): self { $this->titre = $titre; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }

    public function getCreateur(): ?Utilisateur { return $this->createur; }
    public function setCreateur(Utilisateur $createur): self { $this->createur = $createur; return $this; }

    public function getIdCours(): ?int { return $this->idCours; }
    public function setIdCours(?int $idCours): self { $this->idCours = $idCours; return $this; }

    public function getDateCreation(): \DateTimeImmutable { return $this->dateCreation; }
    public function setDateCreation(\DateTimeImmutable $d): self { $this->dateCreation = $d; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $t): self { $this->type = $t; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $s): self { $this->statut = $s; return $this; }

    public function getNombreVues(): int { return $this->nombreVues; }
    public function setNombreVues(int $n): self { $this->nombreVues = $n; return $this; }

    public function getDerniereActivite(): ?\DateTimeImmutable { return $this->derniereActivite; }
    public function setDerniereActivite(?\DateTimeImmutable $d): self { $this->derniereActivite = $d; return $this; }

    public function getTags(): array { return $this->tags ?? []; }
    public function setTags(?array $tags): self { $this->tags = $tags; return $this; }

    public function getReglesModeration(): ?string { return $this->reglesModeration; }
    public function setReglesModeration(?string $r): self { $this->reglesModeration = $r; return $this; }

    public function getLikes(): int { return $this->likes; }
    public function setLikes(int $likes): self { $this->likes = $likes; return $this; }

    public function getDislikes(): int { return $this->dislikes; }
    public function setDislikes(int $dislikes): self { $this->dislikes = $dislikes; return $this; }

    public function getSignalements(): int { return $this->signalements; }
    public function setSignalements(int $signalements): self { $this->signalements = $signalements; return $this; }

    public function isEstModifie(): bool { return $this->estModifie; }
    public function setEstModifie(bool $estModifie): self { $this->estModifie = $estModifie; return $this; }

    public function getDateModification(): ?\DateTimeImmutable { return $this->dateModification; }
    public function setDateModification(?\DateTimeImmutable $dateModification): self { $this->dateModification = $dateModification; return $this; }

    public function incrementVues(): void
    {
        $this->nombreVues++;
        $this->derniereActivite = new \DateTimeImmutable();
    }

    // ============================
    // ✅ VichUploader — Image
    // ============================

    public function getImageCouvertureUrl(): ?string { return $this->imageCouvertureUrl; }
    public function setImageCouvertureUrl(?string $url): self { $this->imageCouvertureUrl = $url; return $this; }

    public function getImageCouvertureFile(): ?File { return $this->imageCouvertureFile; }
    public function setImageCouvertureFile(?File $file): self
    {
        $this->imageCouvertureFile = $file;
        // ✅ Obligatoire pour que Vich déclenche le upload lors de l'edit
        if ($file !== null) {
            $this->dateModification = new \DateTimeImmutable();
        }
        return $this;
    }

    // ============================
    // ✅ VichUploader — Pièce jointe
    // ============================

    public function getPieceJointeUrl(): ?string { return $this->pieceJointeUrl; }
    public function setPieceJointeUrl(?string $url): self { $this->pieceJointeUrl = $url; return $this; }

    public function getPieceJointeFile(): ?File { return $this->pieceJointeFile; }
    public function setPieceJointeFile(?File $file): self
    {
        $this->pieceJointeFile = $file;
        if ($file !== null) {
            $this->dateModification = new \DateTimeImmutable();
        }
        return $this;
    }
}
