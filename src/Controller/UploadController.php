<?php

namespace App\Controller;

use App\Entity\Administrateur;
use App\Entity\Etudiant;
use App\Entity\Enseignant;
use App\Repository\EvaluationRepository;
use App\Repository\SoumissionRepository;
use App\Service\AuthChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/uploads')]
class UploadController extends AbstractController
{
    public function __construct(
        private readonly AuthChecker $authChecker,
        private readonly EvaluationRepository $evaluationRepository,
        private readonly SoumissionRepository $soumissionRepository,
        private readonly KernelInterface $kernel,
    ) {
    }

    #[Route('/{type}/{filename}', name: 'app_upload_file', methods: ['GET'], requirements: ['type' => 'evaluations|soumissions', 'filename' => '.+'])]
    public function serveFile(string $type, string $filename): BinaryFileResponse
    {
        if (!$this->authChecker->isLoggedIn()) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à ce fichier.');
        }

        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
            throw new NotFoundHttpException('Fichier invalide.');
        }

        $baseDirectory = realpath($this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'PIDEV_JAVA-quiz-cours' . DIRECTORY_SEPARATOR . 'uploads');

        if ($baseDirectory === false) {
            throw new NotFoundHttpException('Répertoire de base des uploads introuvable.');
        }

        $allowedTypes = ['evaluations', 'soumissions'];

        if (!in_array($type, $allowedTypes, true)) {
            throw new NotFoundHttpException('Type de fichier inconnu.');
        }

        $directory = $baseDirectory . DIRECTORY_SEPARATOR . $type;
        $filePath = $directory . DIRECTORY_SEPARATOR . $filename;

        if (!is_dir($directory)) {
            throw new NotFoundHttpException(sprintf('Répertoire introuvable : %s', $directory));
        }

        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new NotFoundHttpException(sprintf('Fichier introuvable : %s (chemin attendu : %s)', $filename, $filePath));
        }

        $realDirectory = realpath($directory);
        $realFile = realpath($filePath);
        if ($realDirectory === false || $realFile === false || strncmp($realDirectory, $realFile, strlen($realDirectory)) !== 0) {
            throw new NotFoundHttpException(sprintf('Fichier introuvable après normalisation : %s (chemin résolu : %s)', $filename, $realFile ?: $filePath));
        }

        if (strtolower(pathinfo($realFile, PATHINFO_EXTENSION)) !== 'pdf') {
            throw new NotFoundHttpException('Seuls les fichiers PDF sont autorisés.');
        }

        $currentUser = $this->authChecker->getCurrentUser();

        if ($type === 'evaluations') {
            $evaluation = $this->evaluationRepository->findOneBy(['pdfFilename' => $filename]);
            if (!$evaluation) {
                throw new NotFoundHttpException(sprintf('Fichier introuvable en base de données : %s (chemin : %s)', $filename, $filePath));
            }

            if ($currentUser instanceof Enseignant && $evaluation->getIdEnseignant() === $currentUser->getMatriculeEnseignant()) {
                // accès autorisé pour le propriétaire enseignant
            } elseif ($currentUser instanceof Administrateur) {
                // accès autorisé pour l'administrateur
            } else {
                throw new AccessDeniedHttpException('Accès refusé.');
            }
        } else {
            $soumission = $this->soumissionRepository->findOneBy(['pdfFilename' => $filename]);
            if (!$soumission) {
                throw new NotFoundHttpException(sprintf('Fichier introuvable en base de données : %s (chemin : %s)', $filename, $filePath));
            }

            if ($currentUser instanceof Etudiant && $soumission->getIdEtudiant() === $currentUser->getMatricule()) {
                // accès autorisé pour l'étudiant ayant soumis
            } elseif ($currentUser instanceof Enseignant && $soumission->getEvaluation()->getIdEnseignant() === $currentUser->getMatriculeEnseignant()) {
                // accès autorisé pour l'enseignant responsable de l'évaluation
            } elseif ($currentUser instanceof Administrateur) {
                // accès autorisé pour l'administrateur
            } else {
                throw new AccessDeniedHttpException('Accès refusé.');
            }
        }

        $response = new BinaryFileResponse($realFile);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, basename($realFile));
        $response->headers->set('Content-Type', 'application/pdf');

        return $response;
    }
}
