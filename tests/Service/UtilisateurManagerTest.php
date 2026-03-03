<?php

namespace App\Tests\Service;

use App\Entity\Utilisateur;
use App\Service\UtilisateurManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UtilisateurRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\Test;

class UtilisateurManagerTest extends TestCase
{
    private UtilisateurManager $utilisateurManager;
    private EntityManagerInterface|MockObject $entityManager;
    private UtilisateurRepository|MockObject $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(UtilisateurRepository::class);

        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->with(Utilisateur::class)
            ->willReturn($this->repository);

        $this->utilisateurManager = new UtilisateurManager($this->entityManager);
    }

    // ============================================
    // RÈGLE 1: Tests pour la validation du mot de passe
    // ============================================

    #[Test]
public function passwordValideDevraitPasser(): void
    {
        // Test direct de la méthode validatePassword sans utiliser d'entité
        $resultat = $this->utilisateurManager->validatePassword('Password123');
        $this->assertTrue($resultat);
    }

    #[Test]
public function passwordTropCourtDevraitLeverException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('au moins 8 caractères');

        $this->utilisateurManager->validatePassword('Pass1');
    }

    #[Test]
public function passwordSansMajusculeDevraitLeverException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('au moins une majuscule');

        $this->utilisateurManager->validatePassword('password123');
    }

    #[Test]
public function passwordSansMinusculeDevraitLeverException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('au moins une minuscule');

        $this->utilisateurManager->validatePassword('PASSWORD123');
    }

    #[Test]
public function passwordSansChiffreDevraitLeverException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('au moins un chiffre');

        $this->utilisateurManager->validatePassword('Password');
    }

    // ============================================
    // RÈGLE 2: Tests pour la validation du token
    // ============================================

    private function createUtilisateurConcret(): Utilisateur
    {
        // Utiliser une classe concrète au lieu d'un mock abstrait
        // Si Etudiant n'existe pas, vous pouvez créer une classe anonyme
        return new class extends Utilisateur {};
    }

    private function createUtilisateurAvecToken(?\DateTime $dateExpiration): Utilisateur
    {
        $utilisateur = $this->createUtilisateurConcret();
        $utilisateur->setResetToken('token_123');
        $utilisateur->setResetTokenExpiresAt($dateExpiration);
        return $utilisateur;
    }

    #[Test]
public function tokenValideDevraitPasser(): void
    {
        $utilisateur = $this->createUtilisateurAvecToken(new \DateTime('+1 hour'));

        $resultat = $this->utilisateurManager->validateResetToken($utilisateur);

        $this->assertTrue($resultat);
    }

    #[Test]
public function tokenExpireDevraitLeverException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('expiré ou invalide');

        $utilisateur = $this->createUtilisateurAvecToken(new \DateTime('-1 hour'));

        $this->utilisateurManager->validateResetToken($utilisateur);
    }

    #[Test]
public function tokenNullDevraitLeverException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('expiré ou invalide');

        $utilisateur = $this->createUtilisateurAvecToken(null);

        $this->utilisateurManager->validateResetToken($utilisateur);
    }

    // ============================================
    // RÈGLE 3: Tests pour l'unicité de l'email
    // ============================================

    private function createUtilisateurPourEmail(string $email, int $id = 123): Utilisateur
    {
        $utilisateur = $this->createUtilisateurConcret();
        $utilisateur->setNom('Doe');
        $utilisateur->setPrenom('John');
        $utilisateur->setEmail($email);
        $utilisateur->setMotDePasse('Password123');

        // Pour les tests qui ont besoin d'un ID spécifique
        $property = new \ReflectionProperty(Utilisateur::class, 'id');
        $property->setAccessible(true);
        $property->setValue($utilisateur, $id);

        return $utilisateur;
    }

    #[Test]
public function emailUniqueDevraitPasser(): void
    {
        $utilisateur = $this->createUtilisateurPourEmail('john.doe@example.com');

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'john.doe@example.com'])
            ->willReturn(null);

        $resultat = $this->utilisateurManager->validateEmailUnique($utilisateur);

        $this->assertTrue($resultat);
    }

    #[Test]
public function emailDejaUtiliseDevraitLeverException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('déjà utilisé');

        $utilisateur = $this->createUtilisateurPourEmail('john.doe@example.com', 123);

        // Simuler un autre utilisateur avec le même email
        $existingUser = $this->createUtilisateurPourEmail('john.doe@example.com', 999);

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'john.doe@example.com'])
            ->willReturn($existingUser);

        $this->utilisateurManager->validateEmailUnique($utilisateur);
    }

    #[Test]
public function memeEmailMemeUtilisateurDevraitPasser(): void
    {
        $utilisateur = $this->createUtilisateurPourEmail('john.doe@example.com', 123);

        // Le même utilisateur est trouvé (même ID)
        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'john.doe@example.com'])
            ->willReturn($utilisateur);

        $resultat = $this->utilisateurManager->validateEmailUnique($utilisateur);

        $this->assertTrue($resultat);
    }

    // ============================================
    // Tests de validation complète
    // ============================================

    #[Test]
public function utilisateurValideDevraitPasser(): void
    {
        $utilisateur = $this->createUtilisateurPourEmail('john.doe@example.com');

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'john.doe@example.com'])
            ->willReturn(null);

        $resultat = $this->utilisateurManager->validate($utilisateur);

        $this->assertTrue($resultat);
    }

    #[Test]
public function creationUtilisateurDevraitPersister(): void
    {
        $utilisateur = $this->createUtilisateurPourEmail('john.doe@example.com');

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'john.doe@example.com'])
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($utilisateur);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $resultat = $this->utilisateurManager->createUser($utilisateur);

        $this->assertSame($utilisateur, $resultat);
    }

    #[Test]
public function miseAJourUtilisateurDevraitFlush(): void
    {
        $utilisateur = $this->createUtilisateurPourEmail('john.doe@example.com');

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'john.doe@example.com'])
            ->willReturn($utilisateur); // Même utilisateur

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $resultat = $this->utilisateurManager->updateUser($utilisateur);

        $this->assertSame($utilisateur, $resultat);
    }
}



