<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;

class UtilisateurManager
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Valide toutes les règles métier pour un utilisateur
     */
    public function validate(Utilisateur $utilisateur): bool
    {
        $this->validatePassword($utilisateur->getMotDePasse());
        $this->validateEmailUnique($utilisateur);

        return true;
    }

    /**
     * Règle 1: Vérifie que le mot de passe est sécurisé
     */
    public function validatePassword(string $password): bool
    {
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères.');
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins une majuscule.');
        }

        if (!preg_match('/[a-z]/', $password)) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins une minuscule.');
        }

        if (!preg_match('/[0-9]/', $password)) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins un chiffre.');
        }

        return true;
    }

    /**
     * Règle 2: Vérifie que le token de réinitialisation n'est pas expiré
     */
    public function validateResetToken(Utilisateur $utilisateur): bool
    {
        if (!$utilisateur->isResetTokenValid()) {
            throw new \InvalidArgumentException('Le token de réinitialisation est expiré ou invalide.');
        }

        return true;
    }

    /**
     * Règle 3: Vérifie que l'email est unique (en plus de la validation Doctrine)
     */
    public function validateEmailUnique(Utilisateur $utilisateur): bool
    {
        $existingUser = $this->entityManager
            ->getRepository(Utilisateur::class)
            ->findOneBy(['email' => $utilisateur->getEmail()]);

        if ($existingUser && $existingUser->getId() !== $utilisateur->getId()) {
            throw new \InvalidArgumentException('Cet email est déjà utilisé par un autre utilisateur.');
        }

        return true;
    }

    /**
     * Crée un nouvel utilisateur avec validation
     */
    public function createUser(Utilisateur $utilisateur): Utilisateur
    {
        $this->validate($utilisateur);

        $this->entityManager->persist($utilisateur);
        $this->entityManager->flush();

        return $utilisateur;
    }

    /**
     * Met à jour un utilisateur avec validation
     */
    public function updateUser(Utilisateur $utilisateur): Utilisateur
    {
        $this->validate($utilisateur);

        $this->entityManager->flush();

        return $utilisateur;
    }
}