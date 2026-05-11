<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\QuizRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuizRepository::class)]
#[ORM\Table(name: "quiz")]
class Quiz
{
    // ✅ Constantes des types de quiz
    const TYPE_EVALUATION   = 'evaluation';
    const TYPE_ENTRAINEMENT = 'entrainement';
    const TYPE_REVISION     = 'revision';
    const TYPE_DIAGNOSTIQUE = 'diagnostique';
    const TYPE_AUTOEVAL     = 'autoevaluation';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le titre du quiz est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $titre = null;

    #[ORM\Column(type: "text", nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description = null;

    #[ORM\Column(name: "type_quiz", type: "string", length: 50, nullable: true)]
    private ?string $typeQuiz = null;

    #[ORM\Column(name: "duree_minutes", type: "integer", nullable: true)]
    #[Assert\Positive(message: "La durée doit être un nombre positif")]
    #[Assert\LessThanOrEqual(
        value: 300,
        message: "La durée ne peut pas dépasser {{ compared_value }} minutes (5 heures)"
    )]
    private ?int $dureeMinutes = null;

    #[ORM\Column(name: "nombre_tentatives_autorisees", type: "integer", nullable: true)]
    #[Assert\Positive(message: "Le nombre de tentatives doit être positif")]
    #[Assert\LessThanOrEqual(
        value: 10,
        message: "Le nombre de tentatives ne peut pas dépasser {{ compared_value }}"
    )]
    private ?int $nombreTentativesAutorisees = null;

    #[ORM\Column(name: "difficulte_moyenne", type: "float", nullable: true)]
    #[Assert\Range(
        min: 1,
        max: 10,
        notInRangeMessage: "La difficulté doit être entre {{ min }} et {{ max }}"
    )]
    private ?float $difficulteMoyenne = null;

    #[ORM\Column(type: "text", nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: "Les instructions ne peuvent pas dépasser {{ limit }} caractères"
    )]
    private ?string $instructions = null;

    #[ORM\Column(name: "date_creation", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(name: "date_debut_disponibilite", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $dateDebutDisponibilite = null;

    #[ORM\Column(name: "date_fin_disponibilite", type: "datetime", nullable: true)]
    #[Assert\GreaterThan(
        propertyPath: "dateDebutDisponibilite",
        message: "La date de fin doit être après la date de début"
    )]
    private ?\DateTimeInterface $dateFinDisponibilite = null;

    #[ORM\Column(name: "afficher_correction_apres", type: "string", length: 255, nullable: true)]
    private ?string $afficherCorrectionApres = null;

    #[ORM\Column(name: "id_createur", type: "integer", nullable: true)]
    private ?int $idCreateur = null;

    #[ORM\Column(name: "id_cours", type: "integer", nullable: true)]
    private ?int $idCours = null;

    #[ORM\OneToMany(targetEntity: "App\Entity\Question", mappedBy: "quiz", cascade: ["persist", "remove"], orphanRemoval: true)]
    #[Assert\Valid]
    private Collection $questions;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTypeQuiz(): ?string
    {
        return $this->typeQuiz;
    }

    public function setTypeQuiz(?string $typeQuiz): self
    {
        $this->typeQuiz = $typeQuiz;
        return $this;
    }

    public function getDureeMinutes(): ?int
    {
        return $this->dureeMinutes;
    }

    public function setDureeMinutes(?int $dureeMinutes): self
    {
        $this->dureeMinutes = $dureeMinutes;
        return $this;
    }

    public function getNombreTentativesAutorisees(): ?int
    {
        return $this->nombreTentativesAutorisees;
    }

    public function setNombreTentativesAutorisees(?int $nombreTentativesAutorisees): self
    {
        $this->nombreTentativesAutorisees = $nombreTentativesAutorisees;
        return $this;
    }

    public function getDifficulteMoyenne(): ?float
    {
        return $this->difficulteMoyenne;
    }

    public function setDifficulteMoyenne(?float $difficulteMoyenne): self
    {
        $this->difficulteMoyenne = $difficulteMoyenne;
        return $this;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function setInstructions(?string $instructions): self
    {
        $this->instructions = $instructions;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDateDebutDisponibilite(): ?\DateTimeInterface
    {
        return $this->dateDebutDisponibilite;
    }

    public function setDateDebutDisponibilite(?\DateTimeInterface $dateDebutDisponibilite): self
    {
        $this->dateDebutDisponibilite = $dateDebutDisponibilite;
        return $this;
    }

    public function getDateFinDisponibilite(): ?\DateTimeInterface
    {
        return $this->dateFinDisponibilite;
    }

    public function setDateFinDisponibilite(?\DateTimeInterface $dateFinDisponibilite): self
    {
        $this->dateFinDisponibilite = $dateFinDisponibilite;
        return $this;
    }

    public function getAfficherCorrectionApres(): ?string
    {
        return $this->afficherCorrectionApres;
    }

    public function setAfficherCorrectionApres(?string $afficherCorrectionApres): self
    {
        $this->afficherCorrectionApres = $afficherCorrectionApres;
        return $this;
    }

    public function getIdCreateur(): ?int
    {
        return $this->idCreateur;
    }

    public function setIdCreateur(?int $idCreateur): self
    {
        $this->idCreateur = $idCreateur;
        return $this;
    }

    public function getIdCours(): ?int
    {
        return $this->idCours;
    }

    public function setIdCours(?int $idCours): self
    {
        $this->idCours = $idCours;
        return $this;
    }

    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions[] = $question;
            $question->setQuiz($this);
        }
        return $this;
    }

    public function removeQuestion(Question $question): self
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getQuiz() === $this) {
                $question->setQuiz(null);
            }
        }
        return $this;
    }
}