<?php
namespace App\Tests\Service;

use App\Entity\Quiz;
use App\Service\QuizManager;
use PHPUnit\Framework\TestCase;

class QuizManagerTest extends TestCase
{
    // ✅ Test 1 : Quiz valide
    public function testValidQuiz()
    {
        $quiz = new Quiz();
        $quiz->setTitre('Quiz PHP');
        $quiz->setNombreTentativesAutorisees(3);
        $quiz->setDateDebutDisponibilite(new \DateTime('2026-01-01'));
        $quiz->setDateFinDisponibilite(new \DateTime('2026-06-01'));

        $manager = new QuizManager();
        $this->assertTrue($manager->validate($quiz));
    }

    // ❌ Test 2 : Titre vide
    public function testQuizWithoutTitre()
    {
        $this->expectException(\InvalidArgumentException::class);

        $quiz = new Quiz();
        $quiz->setTitre('');
        $quiz->setNombreTentativesAutorisees(3);

        $manager = new QuizManager();
        $manager->validate($quiz);
    }

    // ❌ Test 3 : Tentatives = 0
    public function testQuizWithZeroTentatives()
    {
        $this->expectException(\InvalidArgumentException::class);

        $quiz = new Quiz();
        $quiz->setTitre('Quiz PHP');
        $quiz->setNombreTentativesAutorisees(0);

        $manager = new QuizManager();
        $manager->validate($quiz);
    }

    // ❌ Test 4 : Date fin avant date début
    public function testQuizWithInvalidDates()
    {
        $this->expectException(\InvalidArgumentException::class);

        $quiz = new Quiz();
        $quiz->setTitre('Quiz PHP');
        $quiz->setDateDebutDisponibilite(new \DateTime('2026-06-01'));
        $quiz->setDateFinDisponibilite(new \DateTime('2026-01-01')); // ← avant début !

        $manager = new QuizManager();
        $manager->validate($quiz);
    }
}