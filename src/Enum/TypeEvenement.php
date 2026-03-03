<?php

namespace App\Enum;

enum TypeEvenement: string
{
    case WEBINAIRE = 'webinaire';
    case EXAMEN    = 'examen';
    case REUNION   = 'reunion';
    case ATELIER   = 'atelier';
}