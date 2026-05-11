<?php

namespace App\Service;

use App\Entity\Cours;

class CalendarIcsService
{
    public function generateCourseIcs(Cours $cours): string
    {
        $start = $cours->getDateDebut() ? \DateTimeImmutable::createFromInterface($cours->getDateDebut())->setTime(9, 0) : new \DateTimeImmutable('+1 day 09:00');
        $end = $cours->getDateFin() ? \DateTimeImmutable::createFromInterface($cours->getDateFin())->setTime(11, 0) : $start->modify('+2 hours');

        $uid = sprintf('cours-%d@novalearn.local', $cours->getId() ?? random_int(1000, 9999));
        $summary = $this->escapeText($cours->getTitre() ?? 'Cours');
        $description = $this->escapeText($cours->getDescription() ?? '');
        $location = $this->escapeText($cours->getModule()?->getTitreModule() ?? 'Online');

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//NovaLearn//Cours//FR',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . gmdate('Ymd\THis\Z'),
            'DTSTART:' . $start->format('Ymd\THis'),
            'DTEND:' . $end->format('Ymd\THis'),
            'SUMMARY:' . $summary,
            'DESCRIPTION:' . $description,
            'LOCATION:' . $location,
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);
    }

    private function escapeText(string $value): string
    {
        return str_replace(["\\", ";", ",", "\n", "\r"], ["\\\\", "\\;", "\\,", "\\n", ''], $value);
    }
}
