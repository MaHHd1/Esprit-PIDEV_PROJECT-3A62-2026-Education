<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\ParticipationEvenement;
use App\Entity\Utilisateur;
use App\Enum\StatutParticipation;
use App\Form\ParticipationEvenementType;
use App\Repository\ParticipationEvenementRepository;
use App\Service\AuthChecker;
use App\Service\EvenementReminderService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/participation-evenement')]
final class ParticipationEvenementController extends AbstractController
{
    private AuthChecker $authChecker;
    private string $appSecret;

    public function __construct(
        AuthChecker $authChecker,
        #[Autowire('%kernel.secret%')] string $appSecret
    ) {
        $this->authChecker = $authChecker;
        $this->appSecret = $appSecret;
    }

    private function getBaseTemplate(): string
    {
        if ($this->authChecker->isEnseignant()) {
            return 'enseignant/teacher_base.html.twig';
        }
        if ($this->authChecker->isEtudiant()) {
            return 'etudiant/student_base.html.twig';
        }
        if ($this->authChecker->isAdmin()) {
            return 'admin/admin_base.html.twig';
        }

        return 'base.html.twig';
    }

    private function getTemplateVariables(EntityManagerInterface $entityManager): array
    {
        $currentUser = $this->authChecker->getCurrentUser();

        $variables = [
            'base_template' => $this->getBaseTemplate(),
        ];

        if ($this->authChecker->isEtudiant()) {
            $variables['student'] = $currentUser;
        } elseif ($this->authChecker->isEnseignant()) {
            $variables['teacher'] = $currentUser;
            $variables['enseignant'] = $currentUser;
        } elseif ($this->authChecker->isAdmin()) {
            $variables['admin'] = $currentUser;
        }

        return $variables;
    }

    #[Route(name: 'app_participation_evenement_index', methods: ['GET'])]
    public function index(
        ParticipationEvenementRepository $participationEvenementRepository,
        EntityManagerInterface $entityManager,
        EvenementReminderService $evenementReminderService
    ): Response {
        if (!$this->authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Vous devez etre connecte.');
            return $this->redirectToRoute('app_login');
        }

        $currentUser = $this->authChecker->getCurrentUser();
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($currentUser->getId());

        if ($utilisateur && $this->authChecker->isEtudiant()) {
            $createdAlerts = $evenementReminderService->notifyStudentUpcomingEvents($utilisateur);
            if ($createdAlerts > 0) {
                $this->addFlash('info', sprintf(
                    'Rappel: %d alerte(s) evenement ont ete ajoutees a votre messagerie.',
                    $createdAlerts
                ));
            }
        }

        if ($this->authChecker->isAdmin()) {
            $participations = $participationEvenementRepository->findAll();
        } else {
            $participations = $participationEvenementRepository->findBy(
                ['utilisateur' => $utilisateur],
                ['dateInscription' => 'DESC']
            );
        }

        return $this->render('participation_evenement/index.html.twig', array_merge(
            $this->getTemplateVariables($entityManager),
            [
                'participation_evenements' => $participations,
            ]
        ));
    }

    #[Route('/new', name: 'app_participation_evenement_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Vous devez etre connecte pour vous inscrire a un evenement.');
            return $this->redirectToRoute('app_login');
        }

        $participation = new ParticipationEvenement();
        $currentUser = $this->authChecker->getCurrentUser();
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($currentUser->getId());

        if (!$utilisateur) {
            $this->addFlash('error', 'Erreur lors de la recuperation de votre profil.');
            return $this->redirectToRoute('app_login');
        }

        $participation->setUtilisateur($utilisateur);

        if ($request->query->has('evenement')) {
            $evenementId = $request->query->getInt('evenement');
            $evenement = $entityManager->getRepository(Evenement::class)->find($evenementId);
            if ($evenement) {
                $participation->setEvenement($evenement);
            } else {
                $this->addFlash('warning', 'L\'evenement demande n\'existe pas.');
            }
        }

        if ($participation->getEvenement()) {
            $alreadyExists = $entityManager->getRepository(ParticipationEvenement::class)
                ->findOneBy([
                    'evenement' => $participation->getEvenement(),
                    'utilisateur' => $utilisateur,
                ]);

            if ($alreadyExists) {
                $this->addFlash('info', 'Vous etes deja inscrit a cet evenement.');
                return $this->redirectToRoute('app_evenement_show', [
                    'id' => $participation->getEvenement()->getId(),
                ]);
            }
        }

        $form = $this->createForm(ParticipationEvenementType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $participation->setUtilisateur($utilisateur);
                $entityManager->persist($participation);
                $entityManager->flush();

                $this->addFlash('success', 'Votre participation a bien ete enregistree.');
                return $this->redirectToRoute('app_participation_evenement_index');
            } catch (UniqueConstraintViolationException) {
                $this->addFlash('error', 'Vous etes deja inscrit a cet evenement.');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Une erreur est survenue : ' . $e->getMessage());
            }
        }

        return $this->render('participation_evenement/new.html.twig', array_merge(
            $this->getTemplateVariables($entityManager),
            [
                'participationEvenement' => $participation,
                'form' => $form,
            ]
        ));
    }

    #[Route('/{id}', name: 'app_participation_evenement_show', methods: ['GET'])]
    public function show(
        ParticipationEvenement $participationEvenement,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Vous devez etre connecte.');
            return $this->redirectToRoute('app_login');
        }

        $currentUser = $this->authChecker->getCurrentUser();
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($currentUser->getId());

        if (!$this->authChecker->isAdmin()
            && $participationEvenement->getUtilisateur()->getId() !== $utilisateur->getId()) {
            $this->addFlash('error', 'Vous n\'avez pas acces a cette participation.');
            return $this->redirectToRoute('app_participation_evenement_index');
        }

        $checkInToken = $this->generateCheckInToken((int) $participationEvenement->getId());
        $checkInUrl = $this->generateUrl(
            'app_participation_evenement_checkin',
            ['id' => $participationEvenement->getId(), 'token' => $checkInToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=230x230&data=' . rawurlencode($checkInUrl);

        return $this->render('participation_evenement/show.html.twig', array_merge(
            $this->getTemplateVariables($entityManager),
            [
                'participation_evenement' => $participationEvenement,
                'checkin_url' => $checkInUrl,
                'checkin_qr_url' => $qrImageUrl,
            ]
        ));
    }

    #[Route('/{id}/check-in', name: 'app_participation_evenement_checkin', methods: ['GET'])]
    public function checkIn(
        Request $request,
        ParticipationEvenement $participationEvenement,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Vous devez etre connecte pour scanner un QR code.');
            return $this->redirectToRoute('app_login');
        }

        $token = (string) $request->query->get('token', '');
        if (!$this->isValidCheckInToken($token, (int) $participationEvenement->getId())) {
            $this->addFlash('danger', 'QR invalide ou expire.');
            return $this->redirectToRoute('app_participation_evenement_show', ['id' => $participationEvenement->getId()]);
        }

        $currentUser = $this->authChecker->getCurrentUser();
        $scanner = $entityManager->getRepository(Utilisateur::class)->find($currentUser->getId());
        $event = $participationEvenement->getEvenement();

        if (!$event) {
            $this->addFlash('danger', 'Evenement introuvable pour cette participation.');
            return $this->redirectToRoute('app_participation_evenement_show', ['id' => $participationEvenement->getId()]);
        }

        $isAdmin = $this->authChecker->isAdmin();
        $isCreator = $scanner && $event->getCreateur() && $event->getCreateur()->getId() === $scanner->getId();
        if (!$isAdmin && !$isCreator) {
            $this->addFlash('danger', 'Fraude detectee: scan non autorise (createur/admin uniquement).');
            return $this->redirectToRoute('app_participation_evenement_show', ['id' => $participationEvenement->getId()]);
        }

        if ($participationEvenement->getHeureArrivee() !== null) {
            $this->addFlash('danger', 'Fraude detectee: double scan. Participation deja validee.');
            return $this->redirectToRoute('app_participation_evenement_show', ['id' => $participationEvenement->getId()]);
        }

        $now = new \DateTimeImmutable();
        $dateDebut = $event->getDateDebut();
        $dateFin = $event->getDateFin();
        if (!$dateDebut || !$dateFin) {
            $this->addFlash('danger', 'Impossible de valider: plage horaire evenement manquante.');
            return $this->redirectToRoute('app_participation_evenement_show', ['id' => $participationEvenement->getId()]);
        }

        $allowedFrom = \DateTimeImmutable::createFromInterface($dateDebut)->modify('-30 minutes');
        $allowedTo = \DateTimeImmutable::createFromInterface($dateFin)->modify('+60 minutes');
        if ($now < $allowedFrom || $now > $allowedTo) {
            $this->addFlash(
                'danger',
                sprintf(
                    'Scan hors plage autorisee (%s -> %s).',
                    $allowedFrom->format('d/m/Y H:i'),
                    $allowedTo->format('d/m/Y H:i')
                )
            );
            return $this->redirectToRoute('app_participation_evenement_show', ['id' => $participationEvenement->getId()]);
        }

        $participationEvenement->setHeureArrivee(new \DateTime());
        $participationEvenement->setStatut(StatutParticipation::PRESENT);
        $entityManager->flush();

        $this->addFlash('success', 'Check-in valide. Heure d\'arrivee enregistree.');
        return $this->redirectToRoute('app_participation_evenement_show', ['id' => $participationEvenement->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_participation_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ParticipationEvenement $participationEvenement,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Vous devez etre connecte.');
            return $this->redirectToRoute('app_login');
        }

        $currentUser = $this->authChecker->getCurrentUser();
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($currentUser->getId());

        if (!$this->authChecker->isAdmin()
            && $participationEvenement->getUtilisateur()->getId() !== $utilisateur->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier cette participation.');
            return $this->redirectToRoute('app_participation_evenement_index');
        }

        $form = $this->createForm(ParticipationEvenementType::class, $participationEvenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Participation modifiee avec succes.');
            return $this->redirectToRoute('app_participation_evenement_index');
        }

        return $this->render('participation_evenement/edit.html.twig', array_merge(
            $this->getTemplateVariables($entityManager),
            [
                'participation_evenement' => $participationEvenement,
                'form' => $form,
            ]
        ));
    }

    #[Route('/{id}', name: 'app_participation_evenement_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        ParticipationEvenement $participationEvenement,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Vous devez etre connecte.');
            return $this->redirectToRoute('app_login');
        }

        $currentUser = $this->authChecker->getCurrentUser();
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($currentUser->getId());

        if (!$this->authChecker->isAdmin()
            && $participationEvenement->getUtilisateur()->getId() !== $utilisateur->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer cette participation.');
            return $this->redirectToRoute('app_participation_evenement_index');
        }

        if ($this->isCsrfTokenValid('delete' . $participationEvenement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($participationEvenement);
            $entityManager->flush();

            $this->addFlash('success', 'Participation supprimee avec succes.');
        }

        return $this->redirectToRoute('app_participation_evenement_index');
    }

    private function generateCheckInToken(int $participationId, int $ttlMinutes = 10): string
    {
        $expiresAt = time() + (max(1, $ttlMinutes) * 60);
        $payload = $participationId . '|' . $expiresAt;
        $signature = hash_hmac('sha256', $payload, $this->appSecret);

        return rtrim(strtr(base64_encode($payload . '|' . $signature), '+/', '-_'), '=');
    }

    private function isValidCheckInToken(string $token, int $expectedParticipationId): bool
    {
        if ($token === '') {
            return false;
        }

        $decoded = base64_decode(strtr($token, '-_', '+/'), true);
        if (!is_string($decoded)) {
            return false;
        }

        $parts = explode('|', $decoded);
        if (count($parts) !== 3) {
            return false;
        }

        [$id, $expiresAt, $signature] = $parts;

        if ((int) $id !== $expectedParticipationId) {
            return false;
        }
        if ((int) $expiresAt < time()) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $id . '|' . $expiresAt, $this->appSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
