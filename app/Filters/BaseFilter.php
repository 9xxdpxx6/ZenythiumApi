<?php

declare(strict_types=1);

namespace App\Filters;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseFilter implements FilterInterface
{
    protected readonly array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    abstract public function apply(Builder $query): Builder;

    protected function applySearch(Builder $query, string $field = 'name'): void
    {
        if (isset($this->filters['search']) && !empty($this->filters['search'])) {
            $query->where($field, 'like', '%' . $this->filters['search'] . '%');
        }
    }

    protected function applySorting(Builder $query, string $defaultField = 'name', string $defaultOrder = 'asc'): void
    {
        $sortBy = $this->filters['sort_by'] ?? $defaultField;
        $sortOrder = $this->filters['sort_order'] ?? $defaultOrder;

        // Validate sort order
        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = $defaultOrder;
        }

        $query->orderBy($sortBy, $sortOrder);
    }

    protected function applyDateRange(Builder $query, string $field = 'created_at'): void
    {
        if (isset($this->filters['date_from'])) {
            $query->where($field, '>=', $this->filters['date_from']);
        }

        if (isset($this->filters['date_to'])) {
            $query->where($field, '<=', $this->filters['date_to']);
        }
    }

    protected function hasFilter(string $key): bool
    {
        return isset($this->filters[$key]) && !empty($this->filters[$key]);
    }

    protected function getFilter(string $key): mixed
    {
        return $this->filters[$key] ?? null;
    }

    public function getPaginationParams(): array
    {
        $perPage = $this->getValidatedPerPage($this->filters['per_page'] ?? $this->getDefaultPerPage());
        return ['per_page' => $perPage];
    }

    protected function getDefaultPerPage(): int
    {
        return 100;
    }

    private function getValidatedPerPage(mixed $perPage): int
    {
        $perPage = (int) $perPage;
        
        // Limit max per page to prevent performance issues
        $perPage = min($perPage, 100);
        
        // Ensure at least 1 item per page
        return max($perPage, 1);
    }
}
