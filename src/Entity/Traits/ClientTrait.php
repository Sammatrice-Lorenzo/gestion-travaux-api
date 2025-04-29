<?php

namespace App\Entity\Traits;

use App\Entity\WorkEventDay;
use Symfony\Component\Serializer\Attribute\Groups;

trait ClientTrait
{
    #[Groups(['read:Client', WorkEventDay::GROUP_WORK_EVENT_DAY_READ, 'read:Work'])]
    final public function getName(): string
    {
        return "{$this->getFirstname()} {$this->getLastname()}";
    }
}
