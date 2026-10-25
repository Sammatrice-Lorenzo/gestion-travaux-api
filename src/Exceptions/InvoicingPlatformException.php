<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Erreur de communication avec la plateforme agréée de facturation électronique, quelle qu'elle soit.
 */
class InvoicingPlatformException extends Exception {}
