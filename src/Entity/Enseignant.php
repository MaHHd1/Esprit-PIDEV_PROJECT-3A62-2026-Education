<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: 'App\Repository\EnseignantRepository')]
#[ORM\Table(name: 'Enseignant')]
#[UniqueEntity(fields: ['matriculeEnseignant'], message: 'Ce matricule enseignant est déjà utilisé.')]
class Enseignant extends Utilisateur
{
    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Le matricule enseignant est obligatoire.')]
    #[Assert\Length(
        min: 5,
        max: 50,
        minMessage: 'Le matricule doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le matricule ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^ENS-[A-Z0-9-]+$/',
        message: 'Le matricule doit commencer par ENS- suivi de lettres majuscules et chiffres.'
    )]
    private ?string $matriculeEnseignant = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le diplôme est obligatoire.')]
    #[Assert\Choice(
        choices: ['Licence', 'Master', 'Doctorat', 'HDR', 'Ingénieur'],
        message: 'Veuillez choisir un diplôme valide.'
    )]
    private ?string $diplome = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'La spécialité est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'La spécialité doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La spécialité ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $specialite = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: 'Les années d\'expérience sont obligatoires.')]
    #[Assert\Range(
        min: 0,
        max: 50,
        notInRangeMessage: 'Les années d\'expérience doivent être entre {{ min }} et {{ max }}.'
    )]
    private ?int $anneesExperience = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Le type de contrat est obligatoire.')]
    #[Assert\Choice(
        choices: ['CDI', 'CDD', 'Vacataire', 'Contractuel'],
        message: 'Veuillez choisir un type de contrat valide.'
    )]
    private ?string $typeContrat = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\When(
        expression: 'this.getTypeContrat() === "Vacataire"',
        constraints: [
            new Assert\NotBlank(message: 'Le taux horaire est obligatoire pour les vacataires.'),
            new Assert\Positive(message: 'Le taux horaire doit être positif.'),
            new Assert\Range(
                min: 10,
                max: 200,
                notInRangeMessage: 'Le taux horaire doit être entre {{ min }} et {{ max }} euros.'
            )
        ]
    )]
    private ?string $tauxHoraire = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'Les disponibilités ne peuvent pas dépasser {{ limit }} caractères.'
    )]
    private ?string $disponibilites = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(
        choices: ['actif', 'inactif', 'conge', 'retraite'],
        message: 'Statut invalide.'
    )]
    private string $statut = 'actif';

    #[ORM\ManyToMany(targetEntity: Cours::class, mappedBy: 'enseignants')]
    private Collection $cours;

    // Getters and Setters
    public function getMatriculeEnseignant(): ?string
    {
        return $this->matriculeEnseignant;
    }

    public function setMatriculeEnseignant(string $matriculeEnseignant): self
    {
        $this->matriculeEnseignant = $matriculeEnseignant;
        return $this;
    }

    public function getDiplome(): ?string
    {
        return $this->diplome;
    }

    public function setDiplome(string $diplome): self
    {
        $this->diplome = $diplome;
        return $this;
    }

    public function getSpecialite(): ?string
    {
        return $this->specialite;
    }

    public function setSpecialite(string $specialite): self
    {
        $this->specialite = $specialite;
        return $this;
    }

    public function getAnneesExperience(): ?int
    {
        return $this->anneesExperience;
    }

    public function setAnneesExperience(int $anneesExperience): self
    {
        $this->anneesExperience = $anneesExperience;
        return $this;
    }

    public function getTypeContrat(): ?string
    {
        return $this->typeContrat;
    }

    public function setTypeContrat(string $typeContrat): self
    {
        $this->typeContrat = $typeContrat;
        return $this;
    }

    public function getTauxHoraire(): ?string
    {
        return $this->tauxHoraire;
    }

    public function setTauxHoraire(?string $tauxHoraire): self
    {
        $this->tauxHoraire = $tauxHoraire;
        return $this;
    }

    public function getDisponibilites(): ?string
    {
        return $this->disponibilites;
    }

    public function setDisponibilites(?string $disponibilites): self
    {
        $this->disponibilites = $disponibilites;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }
    public function __construct()
    {
        parent::__construct();
        $this->cours = new ArrayCollection();
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
            $this->cours->add($cours);
            $cours->addEnseignant($this);
        }

        return $this;
    }

    public function removeCours(Cours $cours): self
    {
        if ($this->cours->removeElement($cours)) {
            $cours->removeEnseignant($this);
        }

        return $this;
    }
    
}