<?php

namespace App\Dto;

final class SupplierDto
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $address = null,
        public readonly ?string $city = null,
        public readonly ?string $country = null,
        public readonly ?string $phone = null,
        public readonly ?string $vatNumber = null,
    ) {}
}
