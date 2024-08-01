<?php

namespace App\Entity\Traits;

use Symfony\Component\Serializer\Attribute\Groups;

trait ClientTrait
{
    #[Groups(['read:Client', 'read:EventDay', 'read:Work'])]
    final public function getName(): string
    {
        return "{$this->getFirstname()} {$this->getLastname()}";
    }
}
