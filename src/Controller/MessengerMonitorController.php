<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;
use Zenstruck\Messenger\Monitor\Controller\MessengerMonitorController as BaseMessengerMonitorController;

/**
 * Tableau de bord Messenger, sans login : hors de /api, il n'est pas routé par le port 443 de l'application.
 * En prod, seul un port lié à l'IP Tailscale y mène, en lecture seule (GET), comme Dozzle.
 */
#[Route('/admin/messenger')]
final class MessengerMonitorController extends BaseMessengerMonitorController {}
