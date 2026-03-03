<?php

namespace App\Entity;

use App\Repository\ResultatQuizRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResultatQuizRepository::class)]
#[ORM\Table(name: "resultat_quiz")]
class ResultatQuiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quiz::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Quiz $quiz = null;

    #[ORM\Column(type: 'integer')]
    private ?int $idEtudiant = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $datePassation = null;

    #[ORM\Column(type: 'float')]
    private ?float $score = null;

    #[ORM\Column(type: 'integer')]
    private ?int $totalPoints = null;

    #[ORM\Column(type: 'integer')]
    private ?int $earnedPoints = null;

    // Stocke les réponses de l'étudiant en JSON
    // Format: [{ questionTexte, studentAnswer, correctAnswer, isCorrect, points }, ...]
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $reponsesEtudiant = null;

    public function getId(): ?int { return $this->id; }

    public function getQuiz(): ?Quiz { return $this->quiz; }
    public function setQuiz(?Quiz $quiz): self { $this->quiz = $quiz; return $this; }

    public function getIdEtudiant(): ?int { return $this->idEtudiant; }
    public function setIdEtudiant(?int $idEtudiant): self { $this->idEtudiant = $idEtudiant; return $this; }

    public function getDatePassation(): ?\DateTimeInterface { return $this->datePassation; }
    public function setDatePassation(?\DateTimeInterface $datePassation): self { $this->datePassation = $datePassation; return $this; }

    public function getScore(): ?float { return $this->score; }
    public function setScore(?float $score): self { $this->score = $score; return $this; }

    public function getTotalPoints(): ?int { return $this->totalPoints; }
    public function setTotalPoints(?int $totalPoints): self { $this->totalPoints = $totalPoints; return $this; }

    public function getEarnedPoints(): ?int { return $this->earnedPoints; }
    public function setEarnedPoints(?int $earnedPoints): self { $this->earnedPoints = $earnedPoints; return $this; }

    public function getReponsesEtudiant(): ?array { return $this->reponsesEtudiant; }
    public function setReponsesEtudiant(?array $reponsesEtudiant): self { $this->reponsesEtudiant = $reponsesEtudiant; return $this; }
}