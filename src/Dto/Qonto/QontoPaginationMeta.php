<?php

declare(strict_types=1);

namespace App\Dto\Qonto;

final readonly class QontoPaginationMeta
{
    public function __construct(
        public int $currentPage,
        public ?int $nextPage,
        public int $totalPages,
        public int $totalCount,
        public int $perPage,
    ) {}
}
