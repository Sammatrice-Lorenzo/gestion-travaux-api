<?php

namespace App\Entity\Traits;


trait ClientTrait
{
    final public function getName(): string
    {
        return "{$this->getFirstname()} {$this->getLastname()}";
    }
}
