<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

trait HasPagination
{
    /**
     * Apply pagination to the query if requested.
     */
    protected function applyPagination(Builder $query, array $filters): Collection|LengthAwarePaginator
    {
        if (isset($filters['per_page'])) {
            $perPage = $this->getValidatedPerPage($filters['per_page']);
            return $query->paginate($perPage);
        }

        return $query->get();
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

