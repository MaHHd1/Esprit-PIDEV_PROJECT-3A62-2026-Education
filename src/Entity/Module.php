<?php

namespace App\Entity;

use App\Repository\ModuleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ModuleRepository::class)]
#[ORM\Table(name: 'module')]
class Module
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le titre du module est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $titreModule = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 5000,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull(message: 'L\'ordre d\'affichage est obligatoire.')]
    #[Assert\Range(
        min: 0,
        max: 9999,
        notInRangeMessage: 'L\'ordre d\'affichage doit être entre {{ min }} et {{ max }}.'
    )]
    private int $ordreAffichage = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 5000,
        maxMessage: 'Les objectifs d\'apprentissage ne peuvent pas dépasser {{ limit }} caractères.'
    )]
    private ?string $objectifsApprentissage = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(
        min: 1,
        max: 5000,
        notInRangeMessage: 'La durée estimée doit être entre {{ min }} et {{ max }} heures.'
    )]
    private ?int $dureeEstimeeHeures = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $datePublication = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[Assert\Choice(
        choices: ['brouillon', 'publie', 'archive'],
        message: 'Le statut doit être l\'un des suivants: brouillon, publie, archive.'
    )]
    private string $statut = 'brouillon';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $ressourcesComplementaires = null;

    #[ORM\OneToMany(mappedBy: 'module', targetEntity: Cours::class, cascade: ['persist','remove'], orphanRemoval: true)]
    private Collection $cours;

    public function __construct()
    {
        $this->cours = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitreModule(): ?string
    {
        return $this->titreModule;
    }

    public function setTitreModule(string $titreModule): self
    {
        $this->titreModule = $titreModule;

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

    public function getOrdreAffichage(): int
    {
        return $this->ordreAffichage;
    }

    public function setOrdreAffichage(int $ordreAffichage): self
    {
        $this->ordreAffichage = $ordreAffichage;

        return $this;
    }

    public function getObjectifsApprentissage(): ?string
    {
        return $this->objectifsApprentissage;
    }

    public function setObjectifsApprentissage(?string $objectifsApprentissage): self
    {
        $this->objectifsApprentissage = $objectifsApprentissage;

        return $this;
    }

    public function getDureeEstimeeHeures(): ?int
    {
        return $this->dureeEstimeeHeures;
    }

    public function setDureeEstimeeHeures(?int $dureeEstimeeHeures): self
    {
        $this->dureeEstimeeHeures = $dureeEstimeeHeures;

        return $this;
    }

    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->datePublication;
    }

    public function setDatePublication(?\DateTimeInterface $datePublication): self
    {
        $this->datePublication = $datePublication;

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

    public function getRessourcesComplementaires(): ?array
    {
        return $this->ressourcesComplementaires;
    }

    public function setRessourcesComplementaires(?array $ressourcesComplementaires): self
    {
        $this->ressourcesComplementaires = $ressourcesComplementaires;

        return $this;
    }

    /**
     * @return Collection|Cours[]
     */
    public function getCours(): Collection
    {
        return $this->cours;
    }

    public function addCours(Cours $cours): self
    {
        if (!$this->cours->contains($cours)) {
            $this->cours[] = $cours;
            $cours->setModule($this);
        }

        return $this;
    }

    public function removeCours(Cours $cours): self
    {
        if ($this->cours->removeElement($cours)) {
            if ($cours->getModule() === $this) {
                $cours->setModule(null);
            }
        }

        return $this;
    }
}
