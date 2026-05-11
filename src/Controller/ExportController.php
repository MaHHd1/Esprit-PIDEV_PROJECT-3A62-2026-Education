<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\EvenementRepository;
use App\Repository\ParticipationEvenementRepository;
use App\Repository\QuizRepository;
use App\Service\AuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/export')]
class ExportController extends AbstractController
{
    public function __construct(
        private Pdf $pdf,
        private QuizRepository $quizRepository,
        private AuthChecker $authChecker,
        private EvenementRepository $evenementRepository,
        private ParticipationEvenementRepository $participationRepository,
        private EntityManagerInterface $entityManager
    ) {}

    private function pdfResponse(string $html, string $filename, string $fallbackRoute): Response
    {
        try {
            return new Response(
                $this->pdf->getOutputFromHtml($html),
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
                ]
            );
        } catch (\Throwable) {
            $this->addFlash(
                'error',
                'Export PDF indisponible: wkhtmltopdf n est pas installe ou mal configure sur le serveur.'
            );

            return $this->redirectToRoute($fallbackRoute);
        }
    }

    #[Route('/enseignant/quiz/{id}/resultats-pdf', name: 'export_teacher_quiz_results_pdf', methods: ['GET'])]
    public function teacherQuizResultsPdf(int $id): Response
    {
        if (!$this->authChecker->isLoggedIn() || !$this->authChecker->isEnseignant()) {
            throw $this->createAccessDeniedException();
        }

        $enseignant = $this->authChecker->getCurrentUser();
        $quiz = $this->quizRepository->find($id);

        if (!$quiz || $quiz->getIdCreateur() !== $enseignant->getId()) {
            throw $this->createNotFoundException('Quiz non trouve');
        }

        $html = $this->renderView('export/teacher_quiz_results.html.twig', [
            'quiz' => $quiz,
            'enseignant' => $enseignant,
            'generated' => new \DateTime(),
        ]);

        return $this->pdfResponse($html, sprintf('quiz-%d-resultats.pdf', $id), 'teacher_quiz_index');
    }

    #[Route('/etudiant/quiz/mes-resultats-pdf', name: 'export_student_results_pdf', methods: ['GET'])]
    public function studentResultsPdf(Request $request): Response
    {
        if (!$this->authChecker->isLoggedIn() || !$this->authChecker->isEtudiant()) {
            throw $this->createAccessDeniedException();
        }

        $student = $this->authChecker->getCurrentUser();
        $session = $request->getSession();
        $data = $session->get('last_quiz_results');

        if (!$data) {
            $this->addFlash('error', 'Aucun resultat disponible. Veuillez d abord soumettre un quiz.');

            return $this->redirectToRoute('quiz_index');
        }

        $html = $this->renderView('export/student_results.html.twig', [
            'data' => $data,
            'student' => $student,
        ]);

        return $this->pdfResponse(
            $html,
            sprintf('mes-resultats-%s.pdf', date('Y-m-d')),
            'quiz_index'
        );
    }

    #[Route('/evenements/liste-pdf', name: 'export_evenements_list_pdf', methods: ['GET'])]
    public function evenementsListPdf(Request $request): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            throw $this->createAccessDeniedException();
        }

        $criteria = [
            'search' => $request->query->get('search'),
            'type' => $request->query->get('type'),
            'statut' => $request->query->get('statut'),
            'visibilite' => $request->query->get('visibilite'),
            'date_debut' => $request->query->get('date_debut'),
            'sort' => $request->query->get('sort', 'e.dateDebut'),
            'direction' => $request->query->get('direction', 'DESC'),
        ];

        $evenements = $this->evenementRepository->search($criteria);
        $eventIds = array_values(array_filter(array_map(
            static fn ($evenement): ?int => $evenement->getId(),
            $evenements
        )));
        $participationCounts = $this->evenementRepository->getParticipationCountsByEventIds($eventIds);

        $html = $this->renderView('export/evenements_list.html.twig', [
            'evenements' => $evenements,
            'criteria' => $criteria,
            'participation_counts' => $participationCounts,
            'generated' => new \DateTimeImmutable(),
        ]);

        return $this->pdfResponse(
            $html,
            sprintf('evenements-%s.pdf', date('Y-m-d')),
            'app_evenement_index'
        );
    }

    #[Route('/participations/liste-pdf', name: 'export_participations_list_pdf', methods: ['GET'])]
    public function participationsListPdf(): Response
    {
        if (!$this->authChecker->isLoggedIn()) {
            throw $this->createAccessDeniedException();
        }

        $currentUser = $this->authChecker->getCurrentUser();
        $utilisateur = $this->entityManager->getRepository(Utilisateur::class)->find($currentUser->getId());

        if (!$utilisateur) {
            throw $this->createNotFoundException('Utilisateur introuvable');
        }

        if ($this->authChecker->isAdmin()) {
            $participations = $this->participationRepository->findBy([], ['dateInscription' => 'DESC']);
        } else {
            $participations = $this->participationRepository->findBy(
                ['utilisateur' => $utilisateur],
                ['dateInscription' => 'DESC']
            );
        }

        $html = $this->renderView('export/participations_list.html.twig', [
            'participations' => $participations,
            'generated' => new \DateTimeImmutable(),
            'is_admin' => $this->authChecker->isAdmin(),
        ]);

        return $this->pdfResponse(
            $html,
            sprintf('participations-%s.pdf', date('Y-m-d')),
            'app_participation_evenement_index'
        );
    }
}
