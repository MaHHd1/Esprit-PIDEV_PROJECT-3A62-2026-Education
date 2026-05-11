<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Ignore;
use App\Entity\Message;
use App\Entity\Evenement;

#[ORM\Entity(repositoryClass: 'App\Repository\UtilisateurRepository')]
#[ORM\Table(name: 'Utilisateur')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'typeUtilisateur', type: 'string')]
#[ORM\DiscriminatorMap([
    'etudiant' => Etudiant::class,
    'enseignant' => Enseignant::class,
    'administrateur' => Administrateur::class
])]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
abstract class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(min: 2, max: 100)]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s\'-]+$/u')]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(min: 2, max: 100)]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s\'-]+$/u')]
    private ?string $prenom = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'L\'email {{ value }} n\'est pas valide.')]
    private ?string $email = null;

    #[ORM\Column(name: 'mot_de_passe', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.')]
    #[Assert\Length(min: 8)]
    #[Assert\Regex(pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/')]
    private ?string $motDePasse = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateCreation = null;

    // PASSWORD RESET
    #[Ignore]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastLogin = null;

    // ============================
    // Relations
    // ============================

    // Messages envoyés
    #[ORM\OneToMany(mappedBy: "expediteur", targetEntity: Message::class)]
    private Collection $messagesEnvoyes;

    // Messages reçus
    #[ORM\OneToMany(mappedBy: "destinataire", targetEntity: Message::class)]
    private Collection $messagesRecus;

    // Événements créés
    #[ORM\OneToMany(mappedBy: "createur", targetEntity: Evenement::class)]
    private Collection $evenementsCrees;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->messagesEnvoyes = new ArrayCollection();
        $this->messagesRecus = new ArrayCollection();
        $this->evenementsCrees = new ArrayCollection();
    }

    // ============================
    // Getters & Setters
    // ============================

    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }
    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): self { $this->prenom = $prenom; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getMotDePasse(): ?string { return $this->motDePasse; }
    public function setMotDePasse(string $motDePasse): self { $this->motDePasse = $motDePasse; return $this; }
    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $dateCreation): self { $this->dateCreation = $dateCreation; return $this; }
    public function getResetToken(): ?string { return $this->resetToken; }
    public function setResetToken(#[\SensitiveParameter] ?string $resetToken): self { $this->resetToken = $resetToken; return $this; }
    public function getResetTokenExpiresAt(): ?\DateTimeInterface { return $this->resetTokenExpiresAt; }
    public function setResetTokenExpiresAt(?\DateTimeInterface $resetTokenExpiresAt): self { $this->resetTokenExpiresAt = $resetTokenExpiresAt; return $this; }
    public function getLastLogin(): ?\DateTimeInterface { return $this->lastLogin; }
    public function setLastLogin(?\DateTimeInterface $lastLogin): self { $this->lastLogin = $lastLogin; return $this; }

    // ============================
    // Helper
    // ============================

    public function getNomComplet(): string { return $this->prenom . ' ' . $this->nom; }

    public function getType(): string
    {
        if ($this instanceof Etudiant) return 'etudiant';
        if ($this instanceof Enseignant) return 'enseignant';
        if ($this instanceof Administrateur) return 'administrateur';
        return 'unknown';
    }

    public function isResetTokenValid(): bool
    {
        return $this->resetToken && $this->resetTokenExpiresAt && $this->resetTokenExpiresAt > new \DateTime();
    }

    // ============================
    // Messages Envoyés
    // ============================

    public function getMessagesEnvoyes(): Collection { return $this->messagesEnvoyes; }

    public function addMessageEnvoye(Message $message): self
    {
        if (!$this->messagesEnvoyes->contains($message)) {
            $this->messagesEnvoyes->add($message);
            $message->setExpediteur($this);
        }
        return $this;
    }

    public function removeMessageEnvoye(Message $message): self
    {
        if ($this->messagesEnvoyes->removeElement($message)) {
            if ($message->getExpediteur() === $this) $message->setExpediteur(null);
        }
        return $this;
    }

    // ============================
    // Messages Reçus
    // ============================

    public function getMessagesRecus(): Collection { return $this->messagesRecus; }

    public function addMessageRecu(Message $message): self
    {
        if (!$this->messagesRecus->contains($message)) {
            $this->messagesRecus->add($message);
            $message->setDestinataire($this);
        }
        return $this;
    }

    public function removeMessageRecu(Message $message): self
    {
        if ($this->messagesRecus->removeElement($message)) {
            if ($message->getDestinataire() === $this) $message->setDestinataire(null);
        }
        return $this;
    }

    // ============================
    // Événements créés
    // ============================

    public function getEvenementsCrees(): Collection { return $this->evenementsCrees; }

    public function addEvenementCree(Evenement $evenement): self
    {
        if (!$this->evenementsCrees->contains($evenement)) {
            $this->evenementsCrees->add($evenement);
            $evenement->setCreateur($this);
        }
        return $this;
    }

    public function removeEvenementCree(Evenement $evenement): self
    {
        if ($this->evenementsCrees->removeElement($evenement)) {
            if ($evenement->getCreateur() === $this) $evenement->setCreateur(null);
        }
        return $this;
    }
 public function getUsername(): string
{
    return $this->prenom . ' ' . $this->nom;
}
}
