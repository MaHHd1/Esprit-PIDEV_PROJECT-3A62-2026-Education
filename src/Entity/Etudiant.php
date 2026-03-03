<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: 'App\Repository\EtudiantRepository')]
#[ORM\Table(name: 'Etudiant')]
#[UniqueEntity(fields: ['matricule'], message: 'Ce matricule est déjà utilisé.')]
class Etudiant extends Utilisateur
{
    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Le matricule est obligatoire.')]
    #[Assert\Length(
        min: 5,
        max: 50,
        minMessage: 'Le matricule doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le matricule ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[A-Z0-9-]+$/',
        message: 'Le matricule ne peut contenir que des lettres majuscules, chiffres et tirets.'
    )]
    private ?string $matricule = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le niveau d\'étude est obligatoire.')]
    #[Assert\Choice(
        choices: ['Licence 1', 'Licence 2', 'Licence 3', 'Master 1', 'Master 2', 'Doctorat'],
        message: 'Veuillez choisir un niveau d\'étude valide.'
    )]
    private ?string $niveauEtude = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'La spécialisation est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'La spécialisation doit contenir au moins {{ limit }} caractères.'
    )]
    private ?string $specialisation = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'La date de naissance est obligatoire.')]
    #[Assert\LessThan(
        value: '-16 years',
        message: 'L\'étudiant doit avoir au moins 16 ans.'
    )]
    #[Assert\GreaterThan(
        value: '-100 years',
        message: 'La date de naissance n\'est pas valide.'
    )]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: 'Le téléphone est obligatoire.')]
    #[Assert\Regex(
        pattern: '/^(\+?[0-9]{1,3})?[0-9]{8,15}$/',
        message: 'Le numéro de téléphone n\'est pas valide.'
    )]
    private ?string $telephone = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'L\'adresse est obligatoire.')]
    #[Assert\Length(
        min: 10,
        max: 500,
        minMessage: 'L\'adresse doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'L\'adresse ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $adresse = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(
        choices: ['actif', 'inactif', 'diplome', 'suspendu'],
        message: 'Statut invalide.'
    )]
    private string $statut = 'actif';

    #[ORM\ManyToMany(targetEntity: Cours::class, inversedBy: 'etudiants')]
    #[ORM\JoinTable(name: 'etudiant_cours')]
    private Collection $coursInscrits;

    public function __construct()
    {
        parent::__construct();
        $this->dateInscription = new \DateTime();
        $this->coursInscrits = new ArrayCollection();
    }

    // Getters and Setters
    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(string $matricule): self
    {
        $this->matricule = $matricule;
        return $this;
    }

    public function getNiveauEtude(): ?string
    {
        return $this->niveauEtude;
    }

    public function setNiveauEtude(string $niveauEtude): self
    {
        $this->niveauEtude = $niveauEtude;
        return $this;
    }

    public function getSpecialisation(): ?string
    {
        return $this->specialisation;
    }

    public function setSpecialisation(string $specialisation): self
    {
        $this->specialisation = $specialisation;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTimeInterface $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeInterface $dateInscription): self
    {
        $this->dateInscription = $dateInscription;
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

    public function getAge(): int
    {
        return $this->dateNaissance->diff(new \DateTime())->y;
    }

    /**
     * @return Collection|Cours[]
     */
    public function getCoursInscrits(): Collection
    {
        return $this->coursInscrits;
    }

    public function addCoursInscrit(Cours $cours): self
    {
        if (!$this->coursInscrits->contains($cours)) {
            $this->coursInscrits->add($cours);
        }

        return $this;
    }

    public function removeCoursInscrit(Cours $cours): self
    {
        $this->coursInscrits->removeElement($cours);

        return $this;
    }

    public function isInscritAuCours(Cours $cours): bool
    {
        return $this->coursInscrits->contains($cours);
    }
}
