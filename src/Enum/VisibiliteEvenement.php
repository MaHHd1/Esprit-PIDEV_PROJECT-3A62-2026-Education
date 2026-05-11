<?php

namespace App\Enum;

enum VisibiliteEvenement: string
{
    case PUBLIC          = 'public';
    case PRIVE           = 'prive';
    case PAR_INVITATION  = 'par_invitation';
}   