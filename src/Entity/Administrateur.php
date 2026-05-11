<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'App\Repository\AdministrateurRepository')]
#[ORM\Table(name: 'Administrateur')]
class Administrateur extends Utilisateur
{
    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le département est obligatoire.')]
    #[Assert\Choice(
        choices: [
            'Direction Générale',
            'Scolarité',
            'Ressources Humaines',
            'Finances',
            'Informatique',
            'Communication',
            'Recherche'
        ],
        message: 'Veuillez choisir un département valide.'
    )]
    private ?string $departement = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'La fonction est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'La fonction doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La fonction ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $fonction = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: '/^(\+?[0-9]{1,3})?[0-9]{8,15}$/',
        message: 'Le numéro de téléphone n\'est pas valide.'
    )]
    private ?string $telephone = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateNomination = null;

    #[ORM\Column(type: 'boolean')]
    private bool $actif = true;

    public function __construct()
    {
        parent::__construct();
        $this->dateNomination = new \DateTime();
    }

    // Getters and Setters
    public function getDepartement(): ?string
    {
        return $this->departement;
    }

    public function setDepartement(string $departement): self
    {
        $this->departement = $departement;
        return $this;
    }

    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function setFonction(string $fonction): self
    {
        $this->fonction = $fonction;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getDateNomination(): ?\DateTimeInterface
    {
        return $this->dateNomination;
    }

    public function setDateNomination(\DateTimeInterface $dateNomination): self
    {
        $this->dateNomination = $dateNomination;
        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): self
    {
        $this->actif = $actif;
        return $this;
    }
}