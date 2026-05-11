<?php

namespace App\Entity;

use App\Repository\CoursTempsPasseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CoursTempsPasseRepository::class)]
#[ORM\Table(name: 'cours_temps_passe')]
#[ORM\UniqueConstraint(name: 'uniq_course_time_student_course', columns: ['etudiant_id', 'cours_id'])]
class CoursTempsPasse
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

    #[ORM\Column(type: 'integer')]
    private int $secondes = 0;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
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

    public function getSecondes(): int
    {
        return $this->secondes;
    }

    public function setSecondes(int $secondes): self
    {
        $this->secondes = max(0, $secondes);

        return $this;
    }

    public function addSecondes(int $secondes): self
    {
        $this->secondes = max(0, $this->secondes + max(0, $secondes));
        $this->updatedAt = new \DateTime();

        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }
}
