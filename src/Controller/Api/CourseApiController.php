<?php

namespace App\Controller\Api;

use App\Entity\Etudiant;
use App\Repository\ContenuRepository;
use App\Repository\CoursRepository;
use App\Service\AuthChecker;
use App\Service\CourseNotificationService;
use App\Service\YouTubeOEmbedService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class CourseApiController extends AbstractController
{
    #[Route('/docs.json', name: 'api_docs_json', methods: ['GET'])]
    public function docs(): JsonResponse
    {
        return $this->json([
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'NovaLearn Course API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/api/youtube/oembed' => ['get' => ['summary' => 'Preview video YouTube via oEmbed']],
                '/api/courses/{id}' => ['get' => ['summary' => 'Consulter cours']],
                '/api/courses/{id}/enroll' => ['post' => ['summary' => 'Inscrire etudiant']],
                '/api/contents/{id}' => ['get' => ['summary' => 'Consulter contenu']],
            ],
        ]);
    }

    #[Route('/youtube/oembed', name: 'api_youtube_oembed', methods: ['GET'])]
    public function youtubePreview(Request $request, YouTubeOEmbedService $youTubeOEmbedService): JsonResponse
    {
        $url = (string) $request->query->get('url', '');
        if ($url === '') {
            return $this->json(['ok' => false, 'message' => 'Parametre url requis.'], 400);
        }

        $preview = $youTubeOEmbedService->fetchPreview($url);
        if ($preview === null) {
            return $this->json(['ok' => false, 'message' => 'Impossible de lire la preview.'], 422);
        }

        return $this->json(['ok' => true, 'preview' => $preview]);
    }

    #[Route('/courses/{id}/enroll', name: 'api_course_enroll', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function enroll(
        int $id,
        AuthChecker $authChecker,
        CoursRepository $coursRepository,
        EntityManagerInterface $entityManager,
        CourseNotificationService $notificationService
    ): JsonResponse {
        $user = $authChecker->getCurrentUser();
        if (!$user instanceof Etudiant) {
            return $this->json(['ok' => false, 'message' => 'Non autorise.'], 403);
        }

        $cours = $coursRepository->find($id);
        if (!$cours || $cours->getStatut() !== 'ouvert') {
            return $this->json(['ok' => false, 'message' => 'Cours non disponible.'], 404);
        }

        $prerequisIds = array_map('intval', array_filter((array) ($cours->getPrerequis() ?? [])));
        if ($prerequisIds !== []) {
            $inscritIds = array_map(
                static fn (\App\Entity\Cours $c): int => (int) $c->getId(),
                $user->getCoursInscrits()->toArray()
            );
            $manquants = array_values(array_diff($prerequisIds, $inscritIds));
            if ($manquants !== []) {
                return $this->json(['ok' => false, 'message' => 'Prerequis non satisfaits.', 'missing_ids' => $manquants], 422);
            }
        }

        if (!$user->isInscritAuCours($cours)) {
            $user->addCoursInscrit($cours);
            $entityManager->flush();
            $notificationService->notifyEnrollment($user, $cours);
        }

        return $this->json([
            'ok' => true,
            'message' => 'Inscription effectuee.',
            'course' => ['id' => $cours->getId(), 'titre' => $cours->getTitre()],
        ]);
    }

    #[Route('/courses/{id}', name: 'api_course_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, CoursRepository $coursRepository): JsonResponse
    {
        $cours = $coursRepository->find($id);
        if (!$cours) {
            return $this->json(['ok' => false, 'message' => 'Cours introuvable.'], 404);
        }

        return $this->json([
            'ok' => true,
            'course' => [
                'id' => $cours->getId(),
                'code' => $cours->getCodeCours(),
                'titre' => $cours->getTitre(),
                'description' => $cours->getDescription(),
                'statut' => $cours->getStatut(),
                'niveau' => $cours->getNiveau(),
                'credits' => $cours->getCredits(),
            ],
        ]);
    }

    #[Route('/contents/{id}', name: 'api_content_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showContent(int $id, ContenuRepository $contenuRepository): JsonResponse
    {
        $contenu = $contenuRepository->find($id);
        if (!$contenu) {
            return $this->json(['ok' => false, 'message' => 'Contenu introuvable.'], 404);
        }

        return $this->json([
            'ok' => true,
            'content' => [
                'id' => $contenu->getId(),
                'titre' => $contenu->getTitre(),
                'types' => $contenu->getTypeContenuList(),
                'resources' => $contenu->getRessourcesForDisplay(),
            ],
        ]);
    }
}
