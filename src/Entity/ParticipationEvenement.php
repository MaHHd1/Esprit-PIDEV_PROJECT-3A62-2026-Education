<?php

namespace App\Entity;

use App\Enum\StatutParticipation;
use App\Repository\ParticipationEvenementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipationEvenementRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_participation', columns: ['evenement_id', 'utilisateur_id'])]
class ParticipationEvenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Evenement $evenement = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(type: 'string', length: 50, enumType: StatutParticipation::class)]
    private StatutParticipation $statut = StatutParticipation::INSCRIT;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureArrivee = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureDepart = null;

    #[ORM\Column(nullable: true)]
    private ?int $feedbackNote = null; // 1 à 5

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $feedbackCommentaire = null;

    public function __construct()
    {
        $this->dateInscription = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): static
    {
        $this->evenement = $evenement;
        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    public function getStatut(): StatutParticipation
    {
        return $this->statut;
    }

    public function setStatut(StatutParticipation $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeInterface $dateInscription): static
    {
        $this->dateInscription = $dateInscription;
        return $this;
    }

    public function getHeureArrivee(): ?\DateTimeInterface
    {
        return $this->heureArrivee;
    }

    public function setHeureArrivee(?\DateTimeInterface $heureArrivee): static
    {
        $this->heureArrivee = $heureArrivee;
        return $this;
    }

    public function getHeureDepart(): ?\DateTimeInterface
    {
        return $this->heureDepart;
    }

    public function setHeureDepart(?\DateTimeInterface $heureDepart): static
    {
        $this->heureDepart = $heureDepart;
        return $this;
    }

    public function getFeedbackNote(): ?int
    {
        return $this->feedbackNote;
    }

    public function setFeedbackNote(?int $feedbackNote): static
    {
        $this->feedbackNote = $feedbackNote;
        return $this;
    }

    public function getFeedbackCommentaire(): ?string
    {
        return $this->feedbackCommentaire;
    }

    public function setFeedbackCommentaire(?string $feedbackCommentaire): static
    {
        $this->feedbackCommentaire = $feedbackCommentaire;
        return $this;
    }
}