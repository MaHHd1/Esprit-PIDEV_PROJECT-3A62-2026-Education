<?php

namespace App\Entity;

use App\Enum\StatutEvenement;
use App\Enum\TypeEvenement;
use App\Enum\VisibiliteEvenement;
use App\Repository\EvenementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50, enumType: TypeEvenement::class)]
    private TypeEvenement $typeEvenement;

    #[ORM\ManyToOne(inversedBy: 'evenementsCrees')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $createur = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieu = null;

    #[ORM\Column(nullable: true)]
    private ?int $capaciteMax = null;

    #[ORM\Column(type: 'string', length: 50, enumType: StatutEvenement::class)]
    private StatutEvenement $statut = StatutEvenement::PLANIFIE;

    #[ORM\Column(type: 'string', length: 50, enumType: VisibiliteEvenement::class)]
    private VisibiliteEvenement $visibilite = VisibiliteEvenement::PUBLIC;

    /**
     * @var Collection<int, ParticipationEvenement>
     */
    #[ORM\OneToMany(targetEntity: ParticipationEvenement::class, mappedBy: 'evenement', orphanRemoval: true)]
    private Collection $participations;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getTypeEvenement(): TypeEvenement
    {
        return $this->typeEvenement;
    }

    public function setTypeEvenement(TypeEvenement $typeEvenement): static
    {
        $this->typeEvenement = $typeEvenement;
        return $this;
    }

    public function getCreateur(): ?Utilisateur
    {
        return $this->createur;
    }

    public function setCreateur(?Utilisateur $createur): static
    {
        $this->createur = $createur;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): static
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getCapaciteMax(): ?int
    {
        return $this->capaciteMax;
    }

    public function setCapaciteMax(?int $capaciteMax): static
    {
        $this->capaciteMax = $capaciteMax;
        return $this;
    }

    public function getStatut(): StatutEvenement
    {
        return $this->statut;
    }

    public function setStatut(StatutEvenement $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getVisibilite(): VisibiliteEvenement
    {
        return $this->visibilite;
    }

    public function setVisibilite(VisibiliteEvenement $visibilite): static
    {
        $this->visibilite = $visibilite;
        return $this;
    }

    /**
     * @return Collection<int, ParticipationEvenement>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(ParticipationEvenement $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setEvenement($this);
        }

        return $this;
    }

    public function removeParticipation(ParticipationEvenement $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getEvenement() === $this) {
                $participation->setEvenement(null);
            }
        }

        return $this;
    }
}