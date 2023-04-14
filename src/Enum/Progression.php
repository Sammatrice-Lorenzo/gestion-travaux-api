<?php

namespace App\Enum;

enum Progression: String
{
    case NOT_STARTED = "Pas commencé";
    case IN_PROGRESS = "En progression";
    case DONE = "Terminé";
}
