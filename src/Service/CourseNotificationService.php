<?php

namespace App\Service;

use App\Entity\Cours;
use App\Entity\Etudiant;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class CourseNotificationService
{
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    public function notifyEnrollment(Etudiant $etudiant, Cours $cours): void
    {
        $subject = 'Inscription au cours: ' . $cours->getTitre();
        $text = sprintf(
            "Bonjour %s,\n\nVotre inscription au cours \"%s\" (%s) a ete confirmee.\n\nCordialement,\nPlateforme",
            $etudiant->getPrenom(),
            $cours->getTitre(),
            $cours->getCodeCours()
        );

        $this->send($etudiant->getEmail(), $subject, $text);
    }

    public function notifyCourseVisibilityChanged(Cours $cours, bool $isVisible): void
    {
        $subject = $isVisible
            ? 'Cours publie: ' . $cours->getTitre()
            : 'Cours masque: ' . $cours->getTitre();
        $text = $isVisible
            ? sprintf("Le cours \"%s\" est maintenant disponible.", $cours->getTitre())
            : sprintf("Le cours \"%s\" a ete masque temporairement.", $cours->getTitre());

        foreach ($cours->getEtudiants() as $etudiant) {
            $this->send($etudiant->getEmail(), $subject, "Bonjour {$etudiant->getPrenom()},\n\n{$text}");
        }
    }

    private function send(?string $to, string $subject, string $text): void
    {
        if ($to === null || trim($to) === '') {
            return;
        }

        $email = (new Email())
            ->from('noreply@novalearn.local')
            ->to($to)
            ->subject($subject)
            ->text($text);

        try {
            $this->mailer->send($email);
        } catch (\Throwable) {
            // Ignore email transport errors in runtime flow.
        }
    }
}
