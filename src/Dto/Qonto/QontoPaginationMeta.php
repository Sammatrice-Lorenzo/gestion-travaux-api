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

    /**
     * @param array<string, mixed> $meta
     */
    public static function fromArray(array $meta): self
    {
        return new self(
            currentPage: (int) ($meta['current_page'] ?? 1),
            nextPage: isset($meta['next_page']) ? (int) $meta['next_page'] : null,
            totalPages: (int) ($meta['total_pages'] ?? 1),
            totalCount: (int) ($meta['total_count'] ?? 0),
            perPage: (int) ($meta['per_page'] ?? 0),
        );
    }
}
