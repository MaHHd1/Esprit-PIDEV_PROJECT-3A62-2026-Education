<?php

namespace App\Service;

use App\Repository\EtudiantRepository;
use App\Repository\EnseignantRepository;
use App\Repository\AdministrateurRepository;
use App\Repository\QuizRepository;
use App\Repository\EvaluationRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GroqChatbotService
{
    private $httpClient;
    private $etudiantRepo;
    private $enseignantRepo;
    private $adminRepo;
    private $quizRepo;
    private $evaluationRepo;
    private $logger;
    private $apiKey;

    public function __construct(
        HttpClientInterface $httpClient,
        EtudiantRepository $etudiantRepo,
        EnseignantRepository $enseignantRepo,
        AdministrateurRepository $adminRepo,
        QuizRepository $quizRepo,
        EvaluationRepository $evaluationRepo,
        LoggerInterface $logger,
        string $groqApiKey
    ) {
        $this->httpClient = $httpClient;
        $this->etudiantRepo = $etudiantRepo;
        $this->enseignantRepo = $enseignantRepo;
        $this->adminRepo = $adminRepo;
        $this->quizRepo = $quizRepo;
        $this->evaluationRepo = $evaluationRepo;
        $this->logger = $logger;
        $this->apiKey = $groqApiKey;
    }

    public function ask(string $question): array
    {
        try {
            // 1. Analyser la question pour comprendre ce que l'utilisateur demande
            $intent = $this->analyzeIntent($question);

            // 2. Récupérer les données pertinentes de la base
            $databaseData = $this->fetchDatabaseData($intent);

            // 3. Formater le contexte pour GROQ
            $context = $this->formatContext($databaseData);

            // 4. Créer le prompt système
            $systemPrompt = $this->createSystemPrompt($context);

            // 5. Appeler GROQ API avec le modèle correct
            $answer = $this->queryGroq($question, $systemPrompt);

            return [
                'success' => true,
                'answer' => $answer,
                'data' => $databaseData
            ];

        } catch (\Exception $e) {
            $this->logger->error('GROQ error: ' . $e->getMessage());
            return [
                'success' => false,
                'answer' => 'Désolé, une erreur technique est survenue. Veuillez réessayer.',
                'error' => $e->getMessage()
            ];
        }
    }

    private function analyzeIntent(string $question): array
    {
        $question = strtolower($question);
        $intent = [
            'type' => 'general',
            'entities' => []
        ];

        // Détecter les types d'utilisateurs mentionnés
        if (strpos($question, 'étudiant') !== false || strpos($question, 'etudiant') !== false) {
            $intent['entities'][] = 'etudiant';
        }
        if (strpos($question, 'enseignant') !== false) {
            $intent['entities'][] = 'enseignant';
        }
        if (strpos($question, 'admin') !== false || strpos($question, 'administrateur') !== false) {
            $intent['entities'][] = 'administrateur';
        }
        if (strpos($question, 'quiz') !== false) {
            $intent['entities'][] = 'quiz';
        }

        // Détecter le type de question
        if (strpos($question, 'combien') !== false || strpos($question, 'nombre') !== false) {
            $intent['type'] = 'count';
        } elseif (strpos($question, 'liste') !== false || strpos($question, 'list') !== false) {
            $intent['type'] = 'list';
        } elseif (strpos($question, 'qui') !== false) {
            $intent['type'] = 'who';
        } elseif (strpos($question, 'statistique') !== false || strpos($question, 'stat') !== false) {
            $intent['type'] = 'stats';
        }

        return $intent;
    }

    private function fetchDatabaseData(array $intent): array
    {
        $data = [];

        // Statistiques générales (toujours disponibles)
        $data['stats'] = [
            'etudiants' => $this->etudiantRepo->count([]),
            'enseignants' => $this->enseignantRepo->count([]),
            'administrateurs' => $this->adminRepo->count([]),
            'quiz' => $this->quizRepo->count([]),
            'evaluations' => $this->evaluationRepo->count([])
        ];

        // Données détaillées selon l'intention
        if (in_array('etudiant', $intent['entities'])) {
            $data['etudiants'] = [
                'total' => $this->etudiantRepo->count([]),
                'actifs' => $this->etudiantRepo->count(['statut' => 'actif']),
                'inactifs' => $this->etudiantRepo->count(['statut' => 'inactif']),
                'recents' => array_map(function($e) {
                    return [
                        'nom' => $e->getNom(),
                        'prenom' => $e->getPrenom(),
                        'email' => $e->getEmail(),
                        'niveau' => $e->getNiveauEtude()
                    ];
                }, $this->etudiantRepo->findBy([], ['dateCreation' => 'DESC'], 5))
            ];
        }

        if (in_array('enseignant', $intent['entities'])) {
            $data['enseignants'] = [
                'total' => $this->enseignantRepo->count([]),
                'actifs' => $this->enseignantRepo->count(['statut' => 'actif']),
                'recents' => array_map(function($e) {
                    return [
                        'nom' => $e->getNom(),
                        'prenom' => $e->getPrenom(),
                        'email' => $e->getEmail(),
                        'specialite' => $e->getSpecialite()
                    ];
                }, $this->enseignantRepo->findBy([], ['dateCreation' => 'DESC'], 5))
            ];
        }

        if (in_array('administrateur', $intent['entities'])) {
            $data['administrateurs'] = [
                'total' => $this->adminRepo->count([]),
                'actifs' => $this->adminRepo->count(['actif' => true]),
                'recents' => array_map(function($a) {
                    return [
                        'nom' => $a->getNom(),
                        'prenom' => $a->getPrenom(),
                        'email' => $a->getEmail(),
                        'fonction' => $a->getFonction()
                    ];
                }, $this->adminRepo->findBy([], ['dateCreation' => 'DESC'], 5))
            ];
        }

        return $data;
    }

    private function formatContext(array $data): string
    {
        $context = "=== INFORMATIONS DE LA BASE DE DONNÉES NOVALEARN ===\n\n";

        $context .= "STATISTIQUES GÉNÉRALES:\n";
        $context .= "- Étudiants: {$data['stats']['etudiants']}\n";
        $context .= "- Enseignants: {$data['stats']['enseignants']}\n";
        $context .= "- Administrateurs: {$data['stats']['administrateurs']}\n";
        $context .= "- Quiz: {$data['stats']['quiz']}\n";
        $context .= "- Évaluations: {$data['stats']['evaluations']}\n\n";

        if (isset($data['etudiants'])) {
            $context .= "DÉTAILS ÉTUDIANTS:\n";
            $context .= "- Total: {$data['etudiants']['total']} (Actifs: {$data['etudiants']['actifs']}, Inactifs: {$data['etudiants']['inactifs']})\n";
            $context .= "- 5 derniers inscrits:\n";
            foreach ($data['etudiants']['recents'] as $e) {
                $context .= "  • {$e['prenom']} {$e['nom']} - {$e['email']} ({$e['niveau']})\n";
            }
            $context .= "\n";
        }

        if (isset($data['enseignants'])) {
            $context .= "DÉTAILS ENSEIGNANTS:\n";
            $context .= "- Total: {$data['enseignants']['total']} (Actifs: {$data['enseignants']['actifs']})\n";
            $context .= "- 5 derniers inscrits:\n";
            foreach ($data['enseignants']['recents'] as $e) {
                $context .= "  • {$e['prenom']} {$e['nom']} - {$e['email']} (Spécialité: {$e['specialite']})\n";
            }
            $context .= "\n";
        }

        if (isset($data['administrateurs'])) {
            $context .= "DÉTAILS ADMINISTRATEURS:\n";
            $context .= "- Total: {$data['administrateurs']['total']} (Actifs: {$data['administrateurs']['actifs']})\n";
            $context .= "- 5 derniers:\n";
            foreach ($data['administrateurs']['recents'] as $a) {
                $context .= "  • {$a['prenom']} {$a['nom']} - {$a['email']} (Fonction: {$a['fonction']})\n";
            }
            $context .= "\n";
        }

        return $context;
    }

    private function createSystemPrompt(string $context): string
    {
        return "Tu es un assistant virtuel pour l'application NovaLearn. Tu aides les administrateurs à gérer la plateforme.

RÈGLES IMPÉRATIVES À SUIVRE ABSOLUMENT:
1. Réponds UNIQUEMENT avec les informations fournies ci-dessous dans le CONTEXTE
2. Si l'information demandée n'est PAS dans le CONTEXTE, réponds: 'Je ne peux pas répondre à cette question car l'information n'est pas disponible dans la base de données.'
3. Ne JAMAIS inventer ou utiliser des connaissances générales
4. Sois concis, professionnel et précis
5. Réponds TOUJOURS en français
6. Ne mentionne JAMAIS que tu utilises des données - réponds naturellement

CONTEXTE (données actuelles de la base):
{$context}

Maintenant, réponds à la question de l'administrateur en utilisant UNIQUEMENT les données ci-dessus.";
    }

    private function queryGroq(string $question, string $systemPrompt): string
    {
        try {
            // Modèles actuellement disponibles sur GROQ (février 2026)
            // mixtral-8x7b-32768 a été décommissionné
            $model = 'llama-3.3-70b-versatile'; // Modèle recommandé

            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $question]
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 500,
                    'top_p' => 0.9,
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 429) {
                return "Trop de requêtes. Veuillez attendre une minute avant de réessayer.";
            }

            if ($statusCode !== 200) {
                $errorContent = $response->getContent(false);
                $this->logger->error("GROQ API error {$statusCode}: {$errorContent}");
                return "Désolé, l'API GROQ est temporairement indisponible.";
            }

            $data = $response->toArray();

            if (isset($data['choices'][0]['message']['content'])) {
                return trim($data['choices'][0]['message']['content']);
            }

            return "Désolé, je n'ai pas pu générer une réponse.";

        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            $this->logger->error('GROQ network error: ' . $e->getMessage());
            return "Erreur de connexion à l'API GROQ. Vérifiez votre connexion internet.";
        } catch (\Exception $e) {
            $this->logger->error('GROQ query error: ' . $e->getMessage());
            return "Désolé, une erreur technique est survenue.";
        }
    }

    public function testApiConnection(): array
    {
        // Vérifions d'abord que la clé API est bien chargée
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'Clé API GROQ non configurée dans .env',
                'api_working' => false
            ];
        }

        try {
            // Test avec le modèle llama-3.3-70b-versatile (disponible)
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'user', 'content' => 'Dis "Bonjour" en français']
                    ],
                    'max_tokens' => 50,
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                $data = $response->toArray();
                return [
                    'success' => true,
                    'status_code' => $statusCode,
                    'response' => $data['choices'][0]['message']['content'] ?? 'OK',
                    'api_working' => true,
                    'message' => '✅ GROQ API fonctionne avec llama-3.3-70b-versatile !'
                ];
            } else {
                $errorContent = $response->getContent(false);
                return [
                    'success' => false,
                    'status_code' => $statusCode,
                    'error' => $errorContent,
                    'api_working' => false
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'api_working' => false,
                'api_key_prefix' => substr($this->apiKey, 0, 10) . '...'
            ];
        }
    }

    public function debugConfig(): array
    {
        return [
            'api_key_exists' => !empty($this->apiKey),
            'api_key_length' => strlen($this->apiKey),
            'api_key_prefix' => substr($this->apiKey, 0, 10) . '...',
            'api_key_format' => str_starts_with($this->apiKey, 'gsk_') ? '✅ Format correct' : '❌ Format incorrect (doit commencer par gsk_)',
        ];
    }

    public function listAvailableModels(): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.groq.com/openai/v1/models', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'timeout' => 10,
            ]);

            $data = $response->toArray();

            $models = [];
            foreach ($data['data'] as $model) {
                $models[] = [
                    'id' => $model['id'],
                    'owned_by' => $model['owned_by'],
                    'context_window' => $model['context_window'] ?? 'N/A',
                ];
            }

            return [
                'success' => true,
                'models' => $models
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getQuickStats(): array
    {
        return [
            'etudiants' => $this->etudiantRepo->count([]),
            'enseignants' => $this->enseignantRepo->count([]),
            'administrateurs' => $this->adminRepo->count([]),
            'quiz' => $this->quizRepo->count([]),
            'evaluations' => $this->evaluationRepo->count([]),
        ];
    }
}