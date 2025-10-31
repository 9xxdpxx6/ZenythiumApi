<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

final class MetricFilter extends BaseFilter
{
    public function apply(Builder $query): Builder
    {
        $this->applySearchFilter($query);
        $this->applyUserFilter($query);
        $this->applyDateRangeFilter($query);
        $this->applyWeightRangeFilter($query);
        $this->applySortingFilter($query);

        return $query;
    }

    private function applySearchFilter(Builder $query): void
    {
        if ($this->hasFilter('search')) {
            $searchTerm = $this->getFilter('search');
            $query->where(function ($q) use ($searchTerm): void {
                $this->applySmartSearch($q, ['note'], $searchTerm);
                $this->applySmartSearchInRelationOr($q, 'user', ['name'], $searchTerm);
            });
        }
    }

    private function applyUserFilter(Builder $query): void
    {
        if ($this->hasFilter('user_id')) {
            $query->where('user_id', $this->getFilter('user_id'));
        }
    }

    private function applyDateRangeFilter(Builder $query): void
    {
        if ($this->hasFilter('date_from')) {
            $query->where('date', '>=', $this->getFilter('date_from'));
        }

        if ($this->hasFilter('date_to')) {
            $query->where('date', '<=', $this->getFilter('date_to') . ' 23:59:59');
        }
    }

    private function applyWeightRangeFilter(Builder $query): void
    {
        if ($this->hasFilter('weight_from')) {
            $query->where('weight', '>=', $this->getFilter('weight_from'));
        }

        if ($this->hasFilter('weight_to')) {
            $query->where('weight', '<=', $this->getFilter('weight_to'));
        }
    }

    private function applySortingFilter(Builder $query): void
    {
        parent::applySorting($query, 'date', 'desc');
    }
}
