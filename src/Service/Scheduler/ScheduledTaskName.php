<?php

declare(strict_types=1);

namespace App\Service\Scheduler;

use Symfony\Component\Console\Messenger\RunCommandMessage;

/**
 * Nom d'une tâche planifiée : « app:qonto:sync-supplier-invoices --from=... » devient « app:qonto:sync-supplier-invoices ».
 */
final class ScheduledTaskName
{
    public static function of(object $message): string
    {
        if ($message instanceof RunCommandMessage) {
            return explode(' ', trim($message->input), 2)[0];
        }

        return $message::class;
    }
}
