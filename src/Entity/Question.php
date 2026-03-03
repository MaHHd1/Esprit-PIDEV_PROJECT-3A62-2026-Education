<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
#[ORM\Table(name: "question")]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Utilisateur $createur = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $id_cours = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $type_quiz = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $date_creation = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $ordre_affichage = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $texte = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $points = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $type_question = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $explication_reponse = null;

    // --------------------------
    // Relation ManyToOne vers Quiz
    // --------------------------
    #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: "questions")]
    #[ORM\JoinColumn(name: "quiz_id", referencedColumnName: "id", nullable: false)]
    private ?Quiz $quiz = null;

    // --------------------------
    // Relation OneToMany vers Reponse
    // --------------------------
    #[ORM\OneToMany(targetEntity: Reponse::class, mappedBy: "question", cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $reponses;

    // --------------------------
    // Constructeur
    // --------------------------
    public function __construct()
    {
        $this->reponses = new ArrayCollection();
    }

    // --------------------------
    // Getters et Setters
    // --------------------------
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): self
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

    public function getCreateur(): ?Utilisateur
    {
        return $this->createur;
    }

    public function setCreateur(?Utilisateur $createur): self
    {
        $this->createur = $createur;
        return $this;
    }

    public function getIdCours(): ?int
    {
        return $this->id_cours;
    }

    public function setIdCours(?int $id_cours): self
    {
        $this->id_cours = $id_cours;
        return $this;
    }

    public function getTypeQuiz(): ?string
    {
        return $this->type_quiz;
    }

    public function setTypeQuiz(?string $type_quiz): self
    {
        $this->type_quiz = $type_quiz;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDateCreation(?\DateTimeInterface $date_creation): self
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function getOrdreAffichage(): ?int
    {
        return $this->ordre_affichage;
    }

    public function setOrdreAffichage(?int $ordre_affichage): self
    {
        $this->ordre_affichage = $ordre_affichage;
        return $this;
    }

    public function getTexte(): ?string
    {
        return $this->texte;
    }

    public function setTexte(?string $texte): self
    {
        $this->texte = $texte;
        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(?int $points): self
    {
        $this->points = $points;
        return $this;
    }

    public function getTypeQuestion(): ?string
    {
        return $this->type_question;
    }

    public function setTypeQuestion(?string $type_question): self
    {
        $this->type_question = $type_question;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getExplicationReponse(): ?string
    {
        return $this->explication_reponse;
    }

    public function setExplicationReponse(?string $explication_reponse): self
    {
        $this->explication_reponse = $explication_reponse;
        return $this;
    }

    // --------------------------
    // Méthodes pour la relation Quiz
    // --------------------------
    
    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): self
    {
        $this->quiz = $quiz;
        return $this;
    }

    // --------------------------
    // Méthodes pour la relation Reponses
    // --------------------------
    
    /**
     * @return Collection|Reponse[]
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(Reponse $reponse): self
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses[] = $reponse;
            $reponse->setQuestion($this);
        }
        return $this;
    }

    public function removeReponse(Reponse $reponse): self
    {
        if ($this->reponses->removeElement($reponse)) {
            if ($reponse->getQuestion() === $this) {
                $reponse->setQuestion(null);
            }
        }
        return $this;
    }

    #[Assert\Callback]
    public function validateCorrectAnswers(ExecutionContextInterface $context): void
    {
        if ($this->type_question === 'choix_multiple' || $this->type_question === 'vrai_faux') {
            $hasCorrectAnswer = false;
            
            foreach ($this->reponses as $reponse) {
                if ($reponse->getEstCorrecte()) {
                    $hasCorrectAnswer = true;
                    break;
                }
            }
            
            if (!$hasCorrectAnswer) {
                $context->buildViolation('Au moins une réponse doit être marquée comme correcte.')
                    ->atPath('reponses')
                    ->addViolation();
            }
        }
    }

    // --------------------------
    // Méthodes utilitaires
    // --------------------------

    public function __toString(): string
    {
        return $this->titre ?? $this->texte ?? 'Question #' . $this->id;
    }

    /**
     * Vérifie si la question est de type QCM
     */
    public function isMultipleChoice(): bool
    {
        return $this->type_question === 'choix_multiple';
    }

    /**
     * Vérifie si la question est de type Vrai/Faux
     */
    public function isTrueFalse(): bool
    {
        return $this->type_question === 'vrai_faux';
    }

    /**
     * Vérifie si la question est de type texte libre
     */
    public function isTextAnswer(): bool
    {
        return $this->type_question === 'texte_libre';
    }

    /**
     * Retourne les réponses correctes
     */
    public function getCorrectAnswers(): Collection
    {
        return $this->reponses->filter(function(Reponse $reponse) {
            return $reponse->getEstCorrecte() === true;
        });
    }

    /**
     * Retourne le nombre de réponses correctes
     */
    public function getCorrectAnswersCount(): int
    {
        return $this->getCorrectAnswers()->count();
    }
}