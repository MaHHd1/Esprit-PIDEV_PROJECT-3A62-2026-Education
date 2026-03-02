<?php

namespace App\Service;

use App\Entity\Cours;

class HuggingFaceCourseAssistantService
{
    private const CHAT_ENDPOINT = 'https://router.huggingface.co/v1/chat/completions';
    private const FALLBACK_MODELS = [
        'deepseek-ai/DeepSeek-R1:fireworks-ai',
        'meta-llama/Llama-3.1-8B-Instruct:novita',
        'mistralai/Mistral-7B-Instruct-v0.3:novita',
    ];

    public function __construct(
        private readonly string $apiKey = '',
        private readonly string $model = ''
    ) {
    }

    /**
     * @param string[] $contentLines
     * @return array{ok: bool, answer: string, error?: string}
     */
    public function askAboutCourse(Cours $course, string $question, array $contentLines = []): array
    {
        $token = trim($this->apiKey);
        if ($token === '') {
            return ['ok' => false, 'answer' => '', 'error' => 'La cle Hugging Face est absente.'];
        }

        $question = trim($question);
        if ($question === '') {
            return ['ok' => false, 'answer' => '', 'error' => 'La question est vide.'];
        }

        $context = $this->buildCourseContext($course, $contentLines);
        $systemPrompt = 'Tu es un assistant pedagogique strict. '
            . 'Tu reponds uniquement sur le cours fourni. '
            . 'Si la question est hors cours, reponds exactement: '
            . '"Je peux uniquement expliquer le contenu de ce cours." '
            . 'Reponse courte, claire, en francais. N invente rien.';

        $userPrompt = "Contexte du cours:\n" . $context . "\n\nQuestion etudiant:\n" . $question;

        $models = $this->getModelCandidates();
        $lastError = 'Erreur Hugging Face inconnue.';

        foreach ($models as $modelName) {
            $result = $this->callChatModel($token, $modelName, $systemPrompt, $userPrompt);
            if ($result['ok']) {
                return $result;
            }

            $lastError = (string) ($result['error'] ?? $lastError);
            $lower = strtolower($lastError);
            $canFallback = str_contains($lower, 'not supported')
                || str_contains($lower, 'not found')
                || str_contains($lower, 'model')
                || str_contains($lower, 'provider');

            if (!$canFallback) {
                return $result;
            }
        }

        return ['ok' => false, 'answer' => '', 'error' => $lastError];
    }

    /**
     * @return string[]
     */
    private function getModelCandidates(): array
    {
        $preferred = trim($this->model);
        if ($preferred === '') {
            return self::FALLBACK_MODELS;
        }

        $all = [$preferred];
        foreach (self::FALLBACK_MODELS as $fallback) {
            if (!in_array($fallback, $all, true)) {
                $all[] = $fallback;
            }
        }

        return $all;
    }

    /**
     * @return array{ok: bool, answer: string, error?: string}
     */
    private function callChatModel(string $token, string $modelName, string $systemPrompt, string $userPrompt): array
    {
        $payload = [
            'model' => $modelName,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.2,
            'max_tokens' => 350,
        ];

        $ch = curl_init(self::CHAT_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 40,
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $curlError !== '') {
            return [
                'ok' => false,
                'answer' => '',
                'error' => 'Erreur reseau Hugging Face: ' . ($curlError !== '' ? $curlError : 'inconnue'),
            ];
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'answer' => '', 'error' => 'Reponse Hugging Face invalide.'];
        }

        if ($httpCode >= 400 || isset($decoded['error'])) {
            $message = (string) ($decoded['error']['message'] ?? $decoded['error'] ?? ('HTTP ' . $httpCode));
            return ['ok' => false, 'answer' => '', 'error' => 'Erreur Hugging Face (' . $modelName . '): ' . $message];
        }

        $answer = trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));
        if ($answer === '') {
            $answer = 'Je peux uniquement expliquer le contenu de ce cours.';
        }

        return ['ok' => true, 'answer' => $answer];
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
}

