<?php

namespace App\Entity;

use App\Repository\CoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CoursRepository::class)]
#[ORM\Table(name: 'cours')]
class Cours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['score:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Le code du cours est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Le code doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le code ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[A-Z0-9-]+$/i',
        message: 'Le code ne peut contenir que des lettres, chiffres et tirets.'
    )]
    #[Groups(['score:read'])]
    private ?string $codeCours = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Groups(['score:read'])]
    private ?string $titre = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 5000,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: Enseignant::class, inversedBy: 'cours')]
    private \Doctrine\Common\Collections\Collection $enseignants;

    #[ORM\ManyToMany(targetEntity: Etudiant::class, mappedBy: 'coursInscrits')]
    private \Doctrine\Common\Collections\Collection $etudiants;

    #[ORM\ManyToOne(targetEntity: Module::class, inversedBy: 'cours')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le module est obligatoire.')]
    private ?Module $module = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Length(
        max: 50,
        maxMessage: 'Le niveau ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $niveau = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(
        min: 1,
        max: 500,
        notInRangeMessage: 'Les crédits doivent être entre {{ min }} et {{ max }}.'
    )]
    private ?int $credits = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Length(
        max: 50,
        maxMessage: 'La langue ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $langue = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dateCreation;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\When(
        expression: 'this.getDateDebut() !== null && value !== null',
        constraints: [
            new Assert\GreaterThanOrEqual(
                propertyPath: 'dateDebut',
                message: 'La date de fin doit être après la date de début.'
            )
        ]
    )]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[Assert\Choice(
        choices: ['brouillon', 'ouvert', 'ferme', 'archive'],
        message: 'Le statut doit être l\'un des suivants: brouillon, ouvert, ferme, archive.'
    )]
    private string $statut = 'brouillon';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $imageCoursUrl = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $prerequis = null;

    #[ORM\OneToMany(mappedBy: 'cours', targetEntity: Contenu::class, cascade: ['persist','remove'], orphanRemoval: true)]
    private Collection $contenus;

    #[ORM\OneToMany(mappedBy: 'cours', targetEntity: Evaluation::class)]
    private Collection $evaluations;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->contenus = new ArrayCollection();
        $this->enseignants = new ArrayCollection();
        $this->etudiants = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeCours(): ?string
    {
        return $this->codeCours;
    }

    public function setCodeCours(string $codeCours): self
    {
        $this->codeCours = $codeCours;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection|Enseignant[]
     */
    public function getEnseignants(): \Doctrine\Common\Collections\Collection
    {
        return $this->enseignants;
    }

    public function addEnseignant(Enseignant $enseignant): self
    {
        if (!$this->enseignants->contains($enseignant)) {
            $this->enseignants[] = $enseignant;
        }

        return $this;
    }

    public function removeEnseignant(Enseignant $enseignant): self
    {
        $this->enseignants->removeElement($enseignant);

        return $this;
    }

    /**
     * @return Collection|Etudiant[]
     */
    public function getEtudiants(): Collection
    {
        return $this->etudiants;
    }

    public function getModule(): ?Module
    {
        return $this->module;
    }

    public function setModule(?Module $module): self
    {
        $this->module = $module;

        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(?string $niveau): self
    {
        $this->niveau = $niveau;

        return $this;
    }

    public function getCredits(): ?int
    {
        return $this->credits;
    }

    public function setCredits(?int $credits): self
    {
        $this->credits = $credits;

        return $this;
    }

    public function getLangue(): ?string
    {
        return $this->langue;
    }

    public function setLangue(?string $langue): self
    {
        $this->langue = $langue;

        return $this;
    }

    public function getDateCreation(): \DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getImageCoursUrl(): ?string
    {
        return $this->imageCoursUrl;
    }

    public function setImageCoursUrl(?string $imageCoursUrl): self
    {
        $this->imageCoursUrl = $imageCoursUrl;

        return $this;
    }

    public function getPrerequis(): ?array
    {
        return $this->prerequis;
    }

    public function setPrerequis(?array $prerequis): self
    {
        $this->prerequis = $prerequis;

        return $this;
    }

    /**
     * @return Collection|Contenu[]
     */
    public function getContenus(): Collection
    {
        return $this->contenus;
    }

    public function addContenu(Contenu $contenu): self
    {
        if (!$this->contenus->contains($contenu)) {
            $this->contenus[] = $contenu;
            $contenu->setCours($this);
        }

        return $this;
    }

    public function removeContenu(Contenu $contenu): self
    {
        if ($this->contenus->removeElement($contenu)) {
            if ($contenu->getCours() === $this) {
                $contenu->setCours(null);
            }
        }

        return $this;
    }

    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(Evaluation $evaluation): self
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations[] = $evaluation;
            $evaluation->setCours($this);
        }

        return $this;
    }

    public function removeEvaluation(Evaluation $evaluation): self
    {
        if ($this->evaluations->removeElement($evaluation)) {
            if ($evaluation->getCours() === $this) {
                $evaluation->setCours(null);
            }
        }

        return $this;
    }
}
