<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Firebase indique que ce token n'est plus enregistré : l'appareil ne recevra plus rien avec ce token.
 */
final class UnregisteredPushTokenException extends RuntimeException {}
