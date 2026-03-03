<?php

namespace App\Controller;

use App\Service\GroqChatbotService;
use App\Service\AuthChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/chatbot')]
class ChatbotController extends AbstractController
{
    #[Route('/', name: 'app_chatbot')]
    public function index(AuthChecker $authChecker): Response
    {
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('admin/chatbot.html.twig');
    }

    #[Route('/ask', name: 'app_chatbot_ask', methods: ['POST'])]
    public function ask(
        Request $request,
        GroqChatbotService $chatbot,
        AuthChecker $authChecker
    ): JsonResponse {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            return $this->json(['success' => false, 'error' => 'Non autorisé'], 403);
        }

        $question = $request->request->get('question');
        if (empty($question)) {
            return $this->json(['success' => false, 'error' => 'Question vide'], 400);
        }

        $result = $chatbot->ask($question);

        return $this->json([
            'success' => $result['success'],
            'question' => $question,
            'answer' => $result['answer'],
            'timestamp' => (new \DateTime())->format('H:i')
        ]);
    }

    #[Route('/stats', name: 'app_chatbot_stats', methods: ['GET'])]
    public function stats(GroqChatbotService $chatbot, AuthChecker $authChecker): JsonResponse
    {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        return $this->json($chatbot->getQuickStats());
    }

    #[Route('/test', name: 'app_chatbot_test')]
    public function test(GroqChatbotService $chatbot, AuthChecker $authChecker): JsonResponse
    {
        if (!$authChecker->isLoggedIn() || !$authChecker->isAdmin()) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        return $this->json($chatbot->testApiConnection());
    }

    #[Route('/test-public', name: 'app_chatbot_test_public')]
    public function testPublic(GroqChatbotService $chatbot): JsonResponse
    {
        return $this->json($chatbot->testApiConnection());
    }

    #[Route('/debug', name: 'app_chatbot_debug')]
    public function debug(GroqChatbotService $chatbot): JsonResponse
    {
        return $this->json($chatbot->debugConfig());
    }

    #[Route('/models', name: 'app_chatbot_models')]
    public function models(GroqChatbotService $chatbot): JsonResponse
    {
        return $this->json($chatbot->listAvailableModels());
    }
}