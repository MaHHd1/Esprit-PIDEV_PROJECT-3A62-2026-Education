<?php

namespace App\Tests\Service;

use App\Entity\Evaluation;
use App\Service\EvaluationManager;
use PHPUnit\Framework\TestCase;

class EvaluationManagerTest extends TestCase
{
    // ✅ Test 1 : Évaluation valide → doit retourner true
    public function testValidEvaluation()
    {
        $evaluation = new Evaluation();
        $evaluation->setTitre('Examen Final');
        $evaluation->setNoteMax('20.00');
        $evaluation->setDateCreation(new \DateTime('2025-01-01'));
        $evaluation->setDateLimite(new \DateTime('2025-06-01'));

        $manager = new EvaluationManager();
        $this->assertTrue($manager->validate($evaluation));
    }

    // ❌ Test 2 : Titre trop court (moins de 3 caractères) → doit lever une exception
    public function testEvaluationWithShortTitre()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire et doit contenir au moins 3 caractères');

        $evaluation = new Evaluation();
        $evaluation->setTitre('AB'); // trop court
        $evaluation->setNoteMax('20.00');
        $evaluation->setDateCreation(new \DateTime('2025-01-01'));
        $evaluation->setDateLimite(new \DateTime('2025-06-01'));

        $manager = new EvaluationManager();
        $manager->validate($evaluation);
    }

    // ❌ Test 3 : Titre vide → doit lever une exception
    public function testEvaluationWithEmptyTitre()
    {
        $this->expectException(\InvalidArgumentException::class);

        $evaluation = new Evaluation();
        $evaluation->setTitre(''); // vide
        $evaluation->setNoteMax('20.00');
        $evaluation->setDateCreation(new \DateTime('2025-01-01'));
        $evaluation->setDateLimite(new \DateTime('2025-06-01'));

        $manager = new EvaluationManager();
        $manager->validate($evaluation);
    }

    // ❌ Test 4 : Note maximale négative → doit lever une exception
    public function testEvaluationWithNegativeNoteMax()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La note maximale doit être supérieure à zéro');

        $evaluation = new Evaluation();
        $evaluation->setTitre('Examen Final');
        $evaluation->setNoteMax('-5.00'); // négatif
        $evaluation->setDateCreation(new \DateTime('2025-01-01'));
        $evaluation->setDateLimite(new \DateTime('2025-06-01'));

        $manager = new EvaluationManager();
        $manager->validate($evaluation);
    }

    // ❌ Test 5 : Note maximale égale à zéro → doit lever une exception
    public function testEvaluationWithZeroNoteMax()
    {
        $this->expectException(\InvalidArgumentException::class);

        $evaluation = new Evaluation();
        $evaluation->setTitre('Examen Final');
        $evaluation->setNoteMax('0.00'); // zéro interdit
        $evaluation->setDateCreation(new \DateTime('2025-01-01'));
        $evaluation->setDateLimite(new \DateTime('2025-06-01'));

        $manager = new EvaluationManager();
        $manager->validate($evaluation);
    }

    // ❌ Test 6 : Date limite antérieure à la date de création → doit lever une exception
    public function testEvaluationWithInvalidDateLimite()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date limite doit être postérieure à la date de création');

        $evaluation = new Evaluation();
        $evaluation->setTitre('Examen Final');
        $evaluation->setNoteMax('20.00');
        $evaluation->setDateCreation(new \DateTime('2025-06-01'));
        $evaluation->setDateLimite(new \DateTime('2025-01-01')); // avant la création !

        $manager = new EvaluationManager();
        $manager->validate($evaluation);
    }

    // ❌ Test 7 : Date limite égale à la date de création → doit lever une exception
    public function testEvaluationWithSameDateLimiteAndDateCreation()
    {
        $this->expectException(\InvalidArgumentException::class);

        $evaluation = new Evaluation();
        $evaluation->setTitre('Examen Final');
        $evaluation->setNoteMax('20.00');
        $evaluation->setDateCreation(new \DateTime('2025-06-01'));
        $evaluation->setDateLimite(new \DateTime('2025-06-01')); // même date !

        $manager = new EvaluationManager();
        $manager->validate($evaluation);
    }
}