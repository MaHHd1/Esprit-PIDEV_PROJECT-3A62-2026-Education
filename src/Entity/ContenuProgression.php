<?php

namespace App\Entity;

use App\Repository\ContenuProgressionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContenuProgressionRepository::class)]
#[ORM\Table(name: 'contenu_progression')]
#[ORM\UniqueConstraint(name: 'uniq_progress_etudiant_contenu', columns: ['etudiant_id', 'contenu_id'])]
class ContenuProgression
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Etudiant::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Etudiant $etudiant = null;

    #[ORM\ManyToOne(targetEntity: Cours::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Cours $cours = null;

    #[ORM\ManyToOne(targetEntity: Contenu::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Contenu $contenu = null;

    #[ORM\Column(type: 'boolean')]
    private bool $estTermine = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateTerminee = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dateCreation;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEtudiant(): ?Etudiant
    {
        return $this->etudiant;
    }

    public function setEtudiant(?Etudiant $etudiant): self
    {
        $this->etudiant = $etudiant;

        return $this;
    }

    public function getCours(): ?Cours
    {
        return $this->cours;
    }

    public function setCours(?Cours $cours): self
    {
        $this->cours = $cours;

        return $this;
    }

    public function getContenu(): ?Contenu
    {
        return $this->contenu;
    }

    public function setContenu(?Contenu $contenu): self
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function isEstTermine(): bool
    {
        return $this->estTermine;
    }

    public function setEstTermine(bool $estTermine): self
    {
        $this->estTermine = $estTermine;

        return $this;
    }

    public function getDateTerminee(): ?\DateTimeInterface
    {
        return $this->dateTerminee;
    }

    public function setDateTerminee(?\DateTimeInterface $dateTerminee): self
    {
        $this->dateTerminee = $dateTerminee;

        return $this;
    }

    public function getDateCreation(): \DateTimeInterface
    {
        return $this->dateCreation;
    }
}
