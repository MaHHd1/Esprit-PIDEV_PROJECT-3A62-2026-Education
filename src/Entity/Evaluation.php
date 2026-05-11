<?php

namespace App\Entity;

use App\Repository\EvaluationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: EvaluationRepository::class)]
class Evaluation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['score:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(min: 3, max: 255)]
    #[Groups(['score:read'])]
    private ?string $titre = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['projet', 'examen'])]
    #[Groups(['score:read'])]
    private ?string $typeEvaluation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000)]
    #[Groups(['score:read'])]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Cours::class, inversedBy: 'evaluations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le cours est obligatoire.')]
    #[Groups(['score:read'])]
    private ?Cours $cours = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['score:read'])]
    private ?string $idEnseignant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['score:read'])]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank]
    #[Groups(['score:read'])]
    private ?\DateTimeInterface $dateLimite = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Groups(['score:read'])]
    private ?string $noteMax = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['en_ligne', 'presentiel'])]
    #[Groups(['score:read'])]
    private ?string $modeRemise = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['ouverte', 'fermee'])]
    #[Groups(['score:read'])]
    private ?string $statut = 'ouverte';

    // ❌ PAS de Groups ici → évite boucle infinie (Evaluation → Soumission → Score → ...)
    #[ORM\OneToMany(mappedBy: 'evaluation', targetEntity: Soumission::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $soumissions;

    #[Vich\UploadableField(mapping: 'evaluation_pdf', fileNameProperty: 'pdfFilename')]
    #[Assert\File(maxSize: '10M', mimeTypes: ['application/pdf'])]
    private ?File $pdfFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['score:read'])]
    private ?string $pdfFilename = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->soumissions = new ArrayCollection();
        $this->dateCreation = new \DateTime();
    }

    // ================= GETTERS & SETTERS (inchangés) =================

    public function getId(): ?int { return $this->id; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getTypeEvaluation(): ?string { return $this->typeEvaluation; }
    public function setTypeEvaluation(string $t): static { $this->typeEvaluation = $t; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }

    public function getCours(): ?Cours { return $this->cours; }
    public function setCours(?Cours $cours): static { $this->cours = $cours; return $this; }

    public function getIdEnseignant(): ?string { return $this->idEnseignant; }
    public function setIdEnseignant(string $id): static { $this->idEnseignant = $id; return $this; }

    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $d): static { $this->dateCreation = $d; return $this; }

    public function getDateLimite(): ?\DateTimeInterface { return $this->dateLimite; }
    public function setDateLimite(\DateTimeInterface $d): static { $this->dateLimite = $d; return $this; }

    public function getNoteMax(): ?string { return $this->noteMax; }
    public function setNoteMax(string $n): static { $this->noteMax = $n; return $this; }

    public function getModeRemise(): ?string { return $this->modeRemise; }
    public function setModeRemise(string $m): static { $this->modeRemise = $m; return $this; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $s): static { $this->statut = $s; return $this; }

    public function getSoumissions(): Collection { return $this->soumissions; }

    public function addSoumission(Soumission $soumission): static
    {
        if (!$this->soumissions->contains($soumission)) {
            $this->soumissions->add($soumission);
            $soumission->setEvaluation($this);
        }
        return $this;
    }

    public function removeSoumission(Soumission $soumission): static
    {
        if ($this->soumissions->removeElement($soumission)) {
            if ($soumission->getEvaluation() === $this) {
                $soumission->setEvaluation(null);
            }
        }
        return $this;
    }

    public function getPdfFile(): ?File { return $this->pdfFile; }
    public function setPdfFile(?File $pdfFile): static
    {
        $this->pdfFile = $pdfFile;
        if ($pdfFile) { $this->updatedAt = new \DateTime(); }
        return $this;
    }

    public function getPdfFilename(): ?string { return $this->pdfFilename; }
    public function setPdfFilename(?string $p): static { $this->pdfFilename = $p; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $u): static { $this->updatedAt = $u; return $this; }
}
