<?php

namespace App\Entity;

use App\Repository\CoursVueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CoursVueRepository::class)]
#[ORM\Table(name: 'cours_vue')]
#[ORM\UniqueConstraint(name: 'uniq_cours_vue_etudiant_cours', columns: ['etudiant_id', 'cours_id'])]
class CoursVue
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

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dateVue;

    public function __construct()
    {
        $this->dateVue = new \DateTime();
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

    public function getDateVue(): \DateTimeInterface
    {
        return $this->dateVue;
    }
}
