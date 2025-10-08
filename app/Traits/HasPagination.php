<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

trait HasPagination
{
    /**
     * Apply pagination to the query.
     */
    protected function applyPagination(Builder $query, array $filters): LengthAwarePaginator
    {
        $perPage = $this->getValidatedPerPage($filters['per_page'] ?? 100);
        return $query->paginate($perPage);
    }

    /**
     * Get validated per page parameter.
     */
    private function getValidatedPerPage(mixed $perPage): int
    {
        $perPage = (int) $perPage;
        
        // Limit max per page to prevent performance issues
        $perPage = min($perPage, 100);
        
        // Ensure at least 1 item per page
        return max($perPage, 1);
    }
}

