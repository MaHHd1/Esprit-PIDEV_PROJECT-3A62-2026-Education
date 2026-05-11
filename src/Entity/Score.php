<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use App\Repository\ScoreRepository;
use App\state\ScoreStateProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ScoreRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(processor: ScoreStateProcessor::class),
        new Put(),
        new Patch(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['score:read']],
    denormalizationContext: ['groups' => ['score:write']],
)]
class Score
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['score:read'])]
    private ?int $id = null;

   #[ORM\OneToOne(inversedBy: 'score', targetEntity: Soumission::class, cascade: ['persist'])]
   #[ORM\JoinColumn(nullable: false, unique: true)]
   #[Assert\NotNull(message: "La soumission est obligatoire")]
   #[Groups(['score:read', 'score:write'])]
    private ?Soumission $soumission = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    #[Groups(['score:read', 'score:write'])]
    private ?string $note = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Groups(['score:read', 'score:write'])]
    private ?string $noteSur = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['score:read', 'score:write'])]
    private ?string $commentaireEnseignant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['score:read'])]
    private ?\DateTimeInterface $dateCorrection = null;

    #[ORM\Column(length: 50)]
    #[Groups(['score:read', 'score:write'])]
    private string $statutCorrection = 'a_corriger';

    public function __construct()
    {
        $this->dateCorrection = new \DateTime();
    }

    #[Groups(['score:read'])]
    public function getPourcentage(): ?float
    {
        if ($this->note !== null && $this->noteSur !== null && (float)$this->noteSur > 0) {
            return round(((float)$this->note / (float)$this->noteSur) * 100, 2);
        }
        return null;
    }

    #[Groups(['score:read'])]
    public function getMention(): ?string
    {
        $p = $this->getPourcentage();
        if ($p === null) return null;
        return match(true) {
            $p >= 90 => 'Excellent',
            $p >= 80 => 'Très bien',
            $p >= 70 => 'Bien',
            $p >= 60 => 'Assez bien',
            $p >= 50 => 'Passable',
            default  => 'Insuffisant',
        };
    }

   #[Groups(['score:read'])]
public function isReussi(): bool
{
    return $this->getPourcentage() !== null && $this->getPourcentage() >= 50;
}

    public function getId(): ?int { return $this->id; }

    public function getSoumission(): ?Soumission { return $this->soumission; }
    public function setSoumission(Soumission $soumission): static { $this->soumission = $soumission; return $this; }

    public function getNote(): ?string { return $this->note; }
    public function setNote(string $note): static { $this->note = $note; return $this; }

    public function getNoteSur(): ?string { return $this->noteSur; }
    public function setNoteSur(string $noteSur): static { $this->noteSur = $noteSur; return $this; }

    public function getCommentaireEnseignant(): ?string { return $this->commentaireEnseignant; }
    public function setCommentaireEnseignant(?string $c): static { $this->commentaireEnseignant = $c; return $this; }

    public function getDateCorrection(): ?\DateTimeInterface { return $this->dateCorrection; }
    public function setDateCorrection(\DateTimeInterface $d): static { $this->dateCorrection = $d; return $this; }

    public function getStatutCorrection(): string { return $this->statutCorrection; }
    public function setStatutCorrection(string $s): static { $this->statutCorrection = $s; return $this; }

    #[Assert\Callback]
    public function validateNote(ExecutionContextInterface $context): void
    {
        if ($this->note !== null && $this->noteSur !== null) {
            if ((float)$this->note > (float)$this->noteSur) {
                $context->buildViolation('La note ne peut pas dépasser la note maximale')
                    ->atPath('note')->addViolation();
            }
        }
    }

    public function __toString(): string
    {
        return sprintf('%s : %s/%s',
            $this->soumission?->getEvaluation()?->getTitre() ?? 'Évaluation',
            $this->note ?? '?',
            $this->noteSur ?? '?'
        );
    }
}