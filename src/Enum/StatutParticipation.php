<?php

namespace App\Enum;

enum StatutParticipation: string
{
    case INSCRIT = 'inscrit';
    case PRESENT = 'present';
    case ABSENT  = 'absent';
    case EXCUSE  = 'excuse';
}