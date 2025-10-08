<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

final class PlanFilter extends BaseFilter
{
    public function apply(Builder $query): Builder
    {
        $this->applySearchFilter($query);
        $this->applyUserFilter($query);
        $this->applyCycleFilter($query);
        $this->applyOrderFilter($query);
        $this->applySortingFilter($query);
        $this->applyDateRangeFilter($query);

        return $query;
    }

    private function applySearchFilter(Builder $query): void
    {
        if ($this->hasFilter('search')) {
            $searchTerm = $this->getFilter('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%');
            });
        }
    }

    private function applyUserFilter(Builder $query): void
    {
        if ($this->hasFilter('user_id')) {
            $query->whereHas('cycle', function ($q) {
                $q->where('user_id', $this->getFilter('user_id'));
            });
        }
    }

    private function applyCycleFilter(Builder $query): void
    {
        if ($this->hasFilter('cycle_id')) {
            $query->where('cycle_id', $this->getFilter('cycle_id'));
        }
    }

    private function applyOrderFilter(Builder $query): void
    {
        if ($this->hasFilter('order')) {
            $query->where('order', $this->getFilter('order'));
        }
    }

    private function applySortingFilter(Builder $query): void
    {
        parent::applySorting($query, 'order', 'asc');
    }

    private function applyDateRangeFilter(Builder $query): void
    {
        parent::applyDateRange($query, 'created_at');
    }
}
