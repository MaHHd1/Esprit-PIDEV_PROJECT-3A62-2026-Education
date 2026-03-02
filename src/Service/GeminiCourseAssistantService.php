<?php

namespace App\Service;

use App\Entity\Cours;

class GeminiCourseAssistantService
{
    private const BASE_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';
    private const FALLBACK_MODELS = [
        'gemini-2.0-flash',
        'gemini-1.5-flash',
        'gemini-1.5-pro',
    ];

    public function __construct(
        private readonly string $geminiApiKey = '',
        private readonly string $geminiModel = ''
    ) {
    }

    /**
     * @param string[] $contentLines
     * @return array{ok: bool, answer: string, error?: string}
     */
    public function askAboutCourse(Cours $course, string $question, array $contentLines = []): array
    {
        $apiKey = trim($this->geminiApiKey);
        if ($apiKey === '') {
            return [
                'ok' => false,
                'answer' => '',
                'error' => 'La cle Gemini est absente. Configurez GEMINI_API_KEY.',
            ];
        }

        $question = trim($question);
        if ($question === '') {
            return [
                'ok' => false,
                'answer' => '',
                'error' => 'La question est vide.',
            ];
        }

        $courseContext = $this->buildCourseContext($course, $contentLines);
        $prompt = <<<PROMPT
Tu es un assistant pedagogique strict.
Regles obligatoires:
1) Reponds uniquement sur ce cours.
2) Si la question est hors cours (politique, religion, code, general, etc.), reponds exactement:
"Je peux uniquement expliquer le contenu de ce cours."
3) Reponse courte, claire, en francais simple, avec explication pedagogique.
4) Ne jamais inventer des informations absentes du contexte.

Contexte du cours:
{$courseContext}

Question etudiant:
{$question}
PROMPT;

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'topK' => 20,
                'topP' => 0.8,
                'maxOutputTokens' => 500,
            ],
        ];

        $models = $this->getModelCandidates();
        $lastError = 'Erreur Gemini inconnue.';

        foreach ($models as $model) {
            $result = $this->callModel($apiKey, $model, $payload);
            if ($result['ok']) {
                return $result;
            }

            $lastError = (string) ($result['error'] ?? $lastError);
            if (!str_contains(strtolower($lastError), 'not found') && !str_contains(strtolower($lastError), 'not supported')) {
                return $result;
            }
        }

        return [
            'ok' => false,
            'answer' => '',
            'error' => $lastError,
        ];
    }

    /**
     * @param string[] $contentLines
     */
    private function buildCourseContext(Cours $course, array $contentLines): string
    {
        $module = $course->getModule();
        $lines = [
            'Titre: ' . (string) ($course->getTitre() ?? ''),
            'Code: ' . (string) ($course->getCodeCours() ?? ''),
            'Module: ' . (string) ($module ? $module->getTitreModule() : ''),
            'Description: ' . (string) ($course->getDescription() ?? ''),
            'Niveau: ' . (string) ($course->getNiveau() ?? ''),
            'Credits: ' . (string) ($course->getCredits() ?? ''),
            'Langue: ' . (string) ($course->getLangue() ?? ''),
        ];

        foreach ($contentLines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $lines[] = '- ' . $trimmed;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @return string[]
     */
    private function getModelCandidates(): array
    {
        $preferred = trim($this->geminiModel);
        if ($preferred === '') {
            return self::FALLBACK_MODELS;
        }

        $models = [$preferred];
        foreach (self::FALLBACK_MODELS as $fallback) {
            if (!in_array($fallback, $models, true)) {
                $models[] = $fallback;
            }
        }

        return $models;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{ok: bool, answer: string, error?: string}
     */
    private function callModel(string $apiKey, string $model, array $payload): array
    {
        $endpoint = sprintf(self::BASE_ENDPOINT, rawurlencode($model));
        $url = $endpoint . '?key=' . rawurlencode($apiKey);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 25,
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $curlError !== '') {
            return [
                'ok' => false,
                'answer' => '',
                'error' => 'Erreur reseau Gemini: ' . ($curlError !== '' ? $curlError : 'inconnue'),
            ];
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return [
                'ok' => false,
                'answer' => '',
                'error' => 'Reponse Gemini invalide.',
            ];
        }

        if ($httpCode >= 400 || isset($decoded['error'])) {
            $message = $decoded['error']['message'] ?? ('HTTP ' . $httpCode);
            return [
                'ok' => false,
                'answer' => '',
                'error' => 'Erreur Gemini (' . $model . '): ' . $message,
            ];
        }

        $answer = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $answer = trim((string) $answer);
        if ($answer === '') {
            $answer = 'Je peux uniquement expliquer le contenu de ce cours.';
        }

        return [
            'ok' => true,
            'answer' => $answer,
        ];
    }
}
