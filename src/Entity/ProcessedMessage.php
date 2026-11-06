<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Messenger\Monitor\History\Model\ProcessedMessage as BaseProcessedMessage;

/**
 * Historique des messages traités par le worker (tableau de bord Messenger), écrit par zenstruck/messenger-monitor-bundle.
 */
#[ORM\Entity(readOnly: true)]
#[ORM\Table('processed_messages')]
class ProcessedMessage extends BaseProcessedMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function id(): ?int
    {
        return $this->id;
    }
}
