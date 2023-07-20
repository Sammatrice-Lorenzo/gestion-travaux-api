<?php

namespace App\Enum;

enum ProgressionEnum: String
{
    case NOT_STARTED = "Pas commencé";
    case IN_PROGRESS = "En cours";
    case DONE = "Terminé";
}
