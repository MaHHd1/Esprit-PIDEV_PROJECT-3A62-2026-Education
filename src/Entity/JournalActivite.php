<?php

namespace App\Entity;

use App\Repository\JournalActiviteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JournalActiviteRepository::class)]
#[ORM\Table(name: 'journal_activite')]
class JournalActivite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $acteurType = 'systeme';

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $acteurId = null;

    #[ORM\Column(type: 'string', length: 120)]
    private string $action;

    #[ORM\Column(type: 'string', length: 50)]
    private string $entiteType;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $entiteId = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $meta = [];

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dateAction;

    public function __construct()
    {
        $this->dateAction = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActeurType(): string
    {
        return $this->acteurType;
    }

    public function setActeurType(string $acteurType): self
    {
        $this->acteurType = $acteurType;

        return $this;
    }

    public function getActeurId(): ?int
    {
        return $this->acteurId;
    }

    public function setActeurId(?int $acteurId): self
    {
        $this->acteurId = $acteurId;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getEntiteType(): string
    {
        return $this->entiteType;
    }

    public function setEntiteType(string $entiteType): self
    {
        $this->entiteType = $entiteType;

        return $this;
    }

    public function getEntiteId(): ?int
    {
        return $this->entiteId;
    }

    public function setEntiteId(?int $entiteId): self
    {
        $this->entiteId = $entiteId;

        return $this;
    }

    public function getMeta(): array
    {
        return $this->meta ?? [];
    }

    public function setMeta(?array $meta): self
    {
        $this->meta = $meta ?? [];

        return $this;
    }

    public function getDateAction(): \DateTimeInterface
    {
        return $this->dateAction;
    }
}
