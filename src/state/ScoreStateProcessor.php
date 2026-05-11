<?php

namespace App\state;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Score;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ScoreStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Score
    {
        /** @var Score $score */
        $score = $data;
        $soumission = $score->getSoumission();

        if (!$soumission) {
            throw new BadRequestHttpException('La soumission est obligatoire.');
        }

        // 1 soumission = 1 score maximum
        if ($soumission->getScore() !== null) {
            throw new BadRequestHttpException('Cette soumission a déjà été corrigée.');
        }

        $score->setDateCorrection(new \DateTime());
        $score->setStatutCorrection('corrige');

        // Liaison bidirectionnelle
        $soumission->setScore($score);

        $this->entityManager->persist($score);
        $this->entityManager->flush();

        return $score;
    }
}