<?php

namespace App\Entity;

use App\Repository\SoumissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: SoumissionRepository::class)]
class Soumission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['score:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'soumissions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'évaluation est obligatoire")]
    #[Groups(['score:read'])]
    private ?Evaluation $evaluation = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "L'identifiant étudiant est obligatoire")]
    #[Groups(['score:read'])]
    private ?string $idEtudiant = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['score:read'])]
    private ?string $fichierSoumissionUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000)]
    #[Groups(['score:read'])]
    private ?string $commentaireEtudiant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['score:read'])]
    private ?\DateTimeInterface $dateSoumission = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(
        choices: ['soumise', 'en_retard', 'non_soumise'],
        message: "Le statut doit être : soumise, en_retard ou non_soumise"
    )]
    #[Groups(['score:read'])]
    private string $statut = 'non_soumise';

    // 🔥 UNE SOUMISSION = UN SCORE
    #[ORM\OneToOne(mappedBy: 'soumission', targetEntity: Score::class, cascade: ['remove'])]
    private ?Score $score = null;

    #[Vich\UploadableField(mapping: 'soumission_pdf', fileNameProperty: 'pdfFilename')]
    #[Assert\File(
        maxSize: '10M',
        mimeTypes: ['application/pdf'],
        mimeTypesMessage: 'Veuillez télécharger un fichier PDF valide'
    )]
    private ?File $pdfFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['score:read'])]
    private ?string $pdfFilename = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->dateSoumission = new \DateTime();
    }

    // ================= GETTERS & SETTERS ================= 
    // (inchangés - gardez les vôtres)

    public function getId(): ?int { return $this->id; }

    public function getEvaluation(): ?Evaluation { return $this->evaluation; }
    public function setEvaluation(?Evaluation $evaluation): static { $this->evaluation = $evaluation; return $this; }

    public function getIdEtudiant(): ?string { return $this->idEtudiant; }
    public function setIdEtudiant(string $idEtudiant): static { $this->idEtudiant = $idEtudiant; return $this; }

    public function getFichierSoumissionUrl(): ?string { return $this->fichierSoumissionUrl; }
    public function setFichierSoumissionUrl(?string $fichierSoumissionUrl): static { $this->fichierSoumissionUrl = $fichierSoumissionUrl; return $this; }

    public function getCommentaireEtudiant(): ?string { return $this->commentaireEtudiant; }
    public function setCommentaireEtudiant(?string $commentaireEtudiant): static { $this->commentaireEtudiant = $commentaireEtudiant; return $this; }

    public function getDateSoumission(): ?\DateTimeInterface { return $this->dateSoumission; }
    public function setDateSoumission(\DateTimeInterface $dateSoumission): static { $this->dateSoumission = $dateSoumission; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getScore(): ?Score { return $this->score; }
    public function setScore(?Score $score): static { $this->score = $score; return $this; }

    public function getPdfFile(): ?File { return $this->pdfFile; }
    public function setPdfFile(?File $pdfFile): static
    {
        $this->pdfFile = $pdfFile;
        if ($pdfFile) { $this->updatedAt = new \DateTime(); }
        return $this;
    }

    public function getPdfFilename(): ?string { return $this->pdfFilename; }
    public function setPdfFilename(?string $pdfFilename): static { $this->pdfFilename = $pdfFilename; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
}