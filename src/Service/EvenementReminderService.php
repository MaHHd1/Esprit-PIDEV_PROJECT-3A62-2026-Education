<?php

namespace App\Service;

use App\Entity\Etudiant;
use App\Entity\Message;
use App\Entity\Utilisateur;
use App\Repository\MessageRepository;
use App\Repository\ParticipationEvenementRepository;
use Doctrine\ORM\EntityManagerInterface;

class EvenementReminderService
{
    public function __construct(
        private ParticipationEvenementRepository $participationRepository,
        private MessageRepository $messageRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function notifyStudentUpcomingEvents(
        Utilisateur $user,
        int $hoursAhead = 24,
        int $duplicateCooldownHours = 6
    ): int {
        if (!$user instanceof Etudiant) {
            return 0;
        }

        $now = new \DateTimeImmutable();
        $until = $now->modify(sprintf('+%d hours', max(1, $hoursAhead)));
        $since = $now->modify(sprintf('-%d hours', max(1, $duplicateCooldownHours)));

        $upcomingParticipations = $this->participationRepository->findUpcomingForUser($user, $now, $until);

        $created = 0;
        foreach ($upcomingParticipations as $participation) {
            $event = $participation->getEvenement();
            if (!$event || !$event->getDateDebut()) {
                continue;
            }

            $eventId = $event->getId();
            if ($eventId === null) {
                continue;
            }

            $subject = sprintf('Rappel événement #%d', $eventId);
            $alreadySent = $this->messageRepository->createQueryBuilder('m')
                ->select('COUNT(m.id)')
                ->andWhere('m.destinataire = :dest')
                ->andWhere('m.objet = :subject')
                ->andWhere('m.categorie = :category')
                ->andWhere('m.dateEnvoi >= :since')
                ->setParameter('dest', $user)
                ->setParameter('subject', $subject)
                ->setParameter('category', 'alerte_evenement')
                ->setParameter('since', $since)
                ->getQuery()
                ->getSingleScalarResult();

            if ((int) $alreadySent > 0) {
                continue;
            }

            $timeLeft = $this->formatTimeLeft($now, $event->getDateDebut());

            $message = new Message();
            $message->setExpediteur($event->getCreateur() ?? $user);
            $message->setDestinataire($user);
            $message->setObjet($subject);
            $message->setCategorie('alerte_evenement');
            $message->setPriorite($this->resolvePriority($now, $event->getDateDebut()));
            $message->setStatut('envoye');
            $message->setDateEnvoi($now);
            $message->setContenu(sprintf(
                "Alerte événement : \"%s\" commence dans %s.\nDate: %s\nLieu: %s",
                $event->getTitre() ?? 'Événement',
                $timeLeft,
                $event->getDateDebut()->format('d/m/Y H:i'),
                $event->getLieu() ?: 'Non précisé'
            ));

            $this->entityManager->persist($message);
            $created++;
        }

        if ($created > 0) {
            $this->entityManager->flush();
        }

        return $created;
    }

    private function resolvePriority(\DateTimeInterface $now, \DateTimeInterface $start): string
    {
        $seconds = $start->getTimestamp() - $now->getTimestamp();
        if ($seconds <= 2 * 3600) {
            return 'urgent';
        }

        return 'normal';
    }

    private function formatTimeLeft(\DateTimeInterface $from, \DateTimeInterface $to): string
    {
        $seconds = max(0, $to->getTimestamp() - $from->getTimestamp());
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($days > 0) {
            return sprintf('%dj %dh', $days, $hours);
        }
        if ($hours > 0) {
            return sprintf('%dh %dmin', $hours, $minutes);
        }

        return sprintf('%dmin', max(1, $minutes));
    }
}
