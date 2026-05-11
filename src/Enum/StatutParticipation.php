<?php

namespace App\Enum;

enum StatutParticipation: string
{
    case INSCRIT = 'inscrit';
    case CONFIRME = 'Confirmé';
    case PRESENT = 'present';
    case ABSENT  = 'absent';
    case EXCUSE  = 'excuse';

    public function label(): string
    {
        return match ($this) {
            self::INSCRIT => 'Inscrit',
            self::CONFIRME => 'Confirme',
            self::PRESENT => 'Present',
            self::ABSENT => 'Absent',
            self::EXCUSE => 'Excuse',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::INSCRIT, self::CONFIRME => 'bg-primary',
            self::PRESENT => 'bg-success',
            self::ABSENT => 'bg-danger',
            self::EXCUSE => 'bg-warning text-dark',
        };
    }
}
