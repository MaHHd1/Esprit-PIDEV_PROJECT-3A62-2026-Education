<?php

namespace App\Enum;

enum StatutEvenement: string
{
    case PLANIFIE     = 'planifie';
    case EN_COURS     = 'en_cours';
    case TERMINE      = 'termine';
    case ANNULE       = 'annule';
}