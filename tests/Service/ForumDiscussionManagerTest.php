<?php

namespace App\Tests\Service;

use App\Entity\ForumDiscussion;
use App\Service\ForumDiscussionManager;
use PHPUnit\Framework\TestCase;

class ForumDiscussionManagerTest extends TestCase
{
    // -------------------------------------------------------
    // Helper : retourne une discussion valide de base
    // -------------------------------------------------------

    private function makeValidDiscussion(): ForumDiscussion
    {
        $discussion = new ForumDiscussion();
        $discussion->setTitre('Aide sur Symfony Unit Test');
        $discussion->setDescription('Comment écrire des tests unitaires avec PHPUnit ?');
        $discussion->setType('public');
        $discussion->setStatut('ouvert');

        return $discussion;
    }

    // -------------------------------------------------------
    // Règle 1 + Règle 2 + Règle 3 + Règle 4 — cas valide
    // -------------------------------------------------------

    /**
     * Une discussion complète et valide doit retourner true.
     */
    public function testValidDiscussion(): void
    {
        $discussion = $this->makeValidDiscussion();
        $manager    = new ForumDiscussionManager();

        $this->assertTrue($manager->validate($discussion));
    }

    /**
     * Une discussion de type 'prive' et statut 'ferme' doit aussi être valide.
     */
    public function testValidDiscussionPriveeFermee(): void
    {
        $discussion = $this->makeValidDiscussion();
        $discussion->setType('prive');
        $discussion->setStatut('ferme');

        $manager = new ForumDiscussionManager();

        $this->assertTrue($manager->validate($discussion));
    }

    // -------------------------------------------------------
    // Règle 1 — titre obligatoire
    // -------------------------------------------------------

    /**
     * Un titre vide doit lever une InvalidArgumentException.
     */
    public function testDiscussionSansTitre(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre de la discussion est obligatoire.');

        $discussion = $this->makeValidDiscussion();
        $discussion->setTitre('');

        (new ForumDiscussionManager())->validate($discussion);
    }

    /**
     * Un titre composé uniquement d'espaces doit être considéré comme vide.
     */
    public function testDiscussionTitreEspacesSeuls(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre de la discussion est obligatoire.');

        $discussion = $this->makeValidDiscussion();
        $discussion->setTitre('     ');

        (new ForumDiscussionManager())->validate($discussion);
    }

    // -------------------------------------------------------
    // Règle 2 — description obligatoire
    // -------------------------------------------------------

    /**
     * Une description vide doit lever une InvalidArgumentException.
     */
    public function testDiscussionSansDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description de la discussion est obligatoire.');

        $discussion = $this->makeValidDiscussion();
        $discussion->setDescription('');

        (new ForumDiscussionManager())->validate($discussion);
    }

    /**
     * Une description composée uniquement d'espaces doit être rejetée.
     */
    public function testDiscussionDescriptionEspacesSeuls(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description de la discussion est obligatoire.');

        $discussion = $this->makeValidDiscussion();
        $discussion->setDescription('   ');

        (new ForumDiscussionManager())->validate($discussion);
    }

    // -------------------------------------------------------
    // Règle 3 — type valide
    // -------------------------------------------------------

    /**
     * Un type inconnu doit lever une InvalidArgumentException.
     */
    public function testDiscussionTypeInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('est invalide');

        $discussion = $this->makeValidDiscussion();
        $discussion->setType('secret');

        (new ForumDiscussionManager())->validate($discussion);
    }

    // -------------------------------------------------------
    // Règle 4 — statut valide
    // -------------------------------------------------------

    /**
     * Un statut inconnu doit lever une InvalidArgumentException.
     */
    public function testDiscussionStatutInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('est invalide');

        $discussion = $this->makeValidDiscussion();
        $discussion->setStatut('archive');

        (new ForumDiscussionManager())->validate($discussion);
    }
}