<?php

namespace App\Entity;

use App\Repository\ContenuRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ContenuRepository::class)]
#[ORM\Table(name: 'contenu')]
class Contenu
{
    public const AVAILABLE_TYPES = ['video', 'pdf', 'ppt', 'texte', 'quiz', 'lien'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cours::class, inversedBy: 'contenus')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le cours est obligatoire.')]
    private ?Cours $cours = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Le type de contenu est obligatoire.')]
    private string $typeContenu = 'texte';

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le titre du contenu est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'Le titre ne peut pas depasser {{ limit }} caracteres.'
    )]
    private ?string $titre = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $urlContenu = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 5000,
        maxMessage: 'La description ne peut pas depasser {{ limit }} caracteres.'
    )]
    private ?string $description = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(
        min: 1,
        max: 10000,
        notInRangeMessage: 'La duree doit etre entre {{ min }} et {{ max }} minutes.'
    )]
    private ?int $duree = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull(message: 'L\'ordre d\'affichage est obligatoire.')]
    #[Assert\Range(
        min: 0,
        max: 9999,
        notInRangeMessage: 'L\'ordre d\'affichage doit etre entre {{ min }} et {{ max }}.'
    )]
    private int $ordreAffichage = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $estPublic = false;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dateAjout;

    #[ORM\Column(type: 'integer')]
    private int $nombreVues = 0;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $format = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $ressources = [];

    public function __construct()
    {
        $this->dateAjout = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getModule(): ?Cours
    {
        return $this->cours;
    }

    public function setModule(?Cours $cours): self
    {
        $this->cours = $cours;

        return $this;
    }

    public function getTypeContenu(): string
    {
        return $this->typeContenu;
    }

    public function setTypeContenu(string $typeContenu): self
    {
        $this->typeContenu = $typeContenu;

        return $this;
    }

    public function getTypeContenuList(): array
    {
        $types = array_filter(array_map('trim', explode(',', $this->typeContenu)));

        if ($types === []) {
            return ['texte'];
        }

        return array_values(array_unique($types));
    }

    public function setTypeContenuFromArray(array $types): self
    {
        $types = array_values(array_unique(array_filter(array_map('trim', $types))));
        $this->typeContenu = implode(',', $types);

        return $this;
    }

    public function hasType(string $type): bool
    {
        return in_array($type, $this->getTypeContenuList(), true);
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getUrlContenu(): ?string
    {
        return $this->urlContenu;
    }

    public function setUrlContenu(?string $urlContenu): self
    {
        $this->urlContenu = $urlContenu;

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

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(?int $duree): self
    {
        $this->duree = $duree;

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

    public function isEstPublic(): bool
    {
        return $this->estPublic;
    }

    public function setEstPublic(bool $estPublic): self
    {
        $this->estPublic = $estPublic;

        return $this;
    }

    public function getDateAjout(): \DateTimeInterface
    {
        return $this->dateAjout;
    }

    public function setDateAjout(\DateTimeInterface $dateAjout): self
    {
        $this->dateAjout = $dateAjout;

        return $this;
    }

    public function getNombreVues(): int
    {
        return $this->nombreVues;
    }

    public function setNombreVues(int $nombreVues): self
    {
        $this->nombreVues = $nombreVues;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getRessources(): array
    {
        return $this->ressources ?? [];
    }

    public function setRessources(?array $ressources): self
    {
        $this->ressources = $ressources ?? [];

        return $this;
    }

    public function getRessourcesForDisplay(): array
    {
        $resources = $this->getRessources();
        if ($resources !== []) {
            return $resources;
        }

        $types = $this->getTypeContenuList();

        if ($this->urlContenu !== null && $this->urlContenu !== '') {
            if (in_array('video', $types, true)) {
                $resources['video_link'] = $this->urlContenu;
            } elseif (in_array('pdf', $types, true)) {
                $resources['pdf'] = $this->urlContenu;
            } elseif (in_array('ppt', $types, true)) {
                $resources['ppt'] = $this->urlContenu;
            } else {
                $resources['lien'] = $this->urlContenu;
            }
        }

        if (in_array('texte', $types, true) && $this->description !== null && trim($this->description) !== '') {
            $resources['texte'] = $this->description;
        }

        return $resources;
    }

    #[Assert\Callback]
    public function validateTypeContenu(ExecutionContextInterface $context): void
    {
        $types = $this->getTypeContenuList();

        if ($types === []) {
            $context->buildViolation('Le type de contenu est obligatoire.')
                ->atPath('typeContenu')
                ->addViolation();

            return;
        }

        foreach ($types as $type) {
            if (!in_array($type, self::AVAILABLE_TYPES, true)) {
                $context->buildViolation('Un type de contenu est invalide.')
                    ->atPath('typeContenu')
                    ->addViolation();

                return;
            }
        }
    }
}
