<?php

declare(strict_types=1);

namespace App\Exceptions;

use LogicException;

/**
 * Le compte relié à la plateforme agréée (QONTO_USER_EMAIL) n'est pas configuré ou ne correspond à aucun utilisateur.
 */
final class InvoicingPlatformAccountException extends LogicException {}
