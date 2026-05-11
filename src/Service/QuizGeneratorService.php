<?php

namespace App\Service;

use App\Repository\CoursRepository;

class QuizGeneratorService
{
    public function __construct(
        private GroqService $groq,
        private CoursRepository $coursRepository
    ) {}

    public function generateFromSubject(string $subject, int $nbQuestions, string $level): array
    {
        $prompt = "Génère un quiz de $nbQuestions questions sur le sujet : \"$subject\".
Niveau : $level.
Chaque question doit avoir 4 choix (A, B, C, D), une bonne réponse et une explication courte.";

        return $this->groq->generateQuiz($prompt);
    }

    public function generateFromDocument(string $content, int $nbQuestions): array
    {
        $content = mb_substr($content, 0, 3000);
        $prompt  = "Voici un document de cours :\n\n$content\n\nGénère un quiz de $nbQuestions questions basées sur ce contenu. Chaque question doit avoir 4 choix (A, B, C, D), une bonne réponse et une explication.";

        return $this->groq->generateQuiz($prompt);
    }

    public function generateFromCours(int $coursId, int $nbQuestions): array
    {
        $cours = $this->coursRepository->find($coursId);

        if (!$cours) {
            throw new \Exception('Cours introuvable.');
        }

        // ── 1. Collecter les contenus texte publics ──────────────────────────
        $texteContenus = [];
        foreach ($cours->getContenus() as $contenu) {
            if (!$contenu->isEstPublic()) {
                continue;
            }
            $resources = $contenu->getRessourcesForDisplay();
            if (!empty($resources['texte']) && trim($resources['texte']) !== '') {
                $texteContenus[] = [
                    'titre' => $contenu->getTitre() ?? 'Contenu',
                    'texte' => $resources['texte'],
                ];
            }
        }

        // ── 2. Des contenus texte existent → quiz basé sur le contenu réel ───
        if (!empty($texteContenus)) {
            $context = '';
            foreach ($texteContenus as $c) {
                $context .= "\n--- " . $c['titre'] . " ---\n";
                $context .= mb_substr($c['texte'], 0, 1000) . "\n";
            }
            $context = mb_substr($context, 0, 4000);

            $prompt = <<<PROMPT
Voici le contenu du cours "{$cours->getTitre()}" :

{$context}

Génère un quiz de {$nbQuestions} questions basées UNIQUEMENT sur ce contenu.
Les questions doivent tester la compréhension et la mémorisation du contenu ci-dessus.
Chaque question doit avoir 4 choix (A, B, C, D), une bonne réponse et une explication courte.
PROMPT;

        // ── 3. Pas de contenu texte → quiz basé sur le thème/métadonnées ─────
        } else {
            $meta = "Titre du cours : " . $cours->getTitre() . "\n";
            $meta .= "Code : " . $cours->getCodeCours() . "\n";

            if ($cours->getNiveau()) {
                $meta .= "Niveau : " . $cours->getNiveau() . "\n";
            }
            if ($cours->getDescription()) {
                $meta .= "Description : " . mb_substr($cours->getDescription(), 0, 500) . "\n";
            }
            if ($cours->getModule()) {
                $meta .= "Module : " . $cours->getModule()->getTitreModule() . "\n";
            }

            $prompt = <<<PROMPT
Voici les informations sur un cours :

{$meta}

Ce cours ne contient pas encore de contenu textuel détaillé.
Génère un quiz de {$nbQuestions} questions sur le thème général de ce cours, en te basant sur son titre, sa description et son domaine.
Les questions doivent être pertinentes et adaptées au niveau indiqué.
Chaque question doit avoir 4 choix (A, B, C, D), une bonne réponse et une explication courte.
PROMPT;
        }

        return $this->groq->generateQuiz($prompt);
    }
}
