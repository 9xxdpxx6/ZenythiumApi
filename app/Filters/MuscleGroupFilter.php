<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

final class MuscleGroupFilter extends BaseFilter
{
    public function apply(Builder $query): Builder
    {
        $this->applySearchFilter($query);
        $this->applyUserFilter($query);
        $this->applySortingFilter($query);
        $this->applyDateRangeFilter($query);

        return $query;
    }

    private function applySearchFilter(Builder $query): void
    {
        parent::applySearch($query, 'name');
    }

    private function applyUserFilter(Builder $query): void
    {
        if ($this->hasFilter('user_id')) {
            $query->withCount(['exercises' => function ($query) {
                $query->where('user_id', $this->getFilter('user_id'));
            }]);
        }
    }

    private function applySortingFilter(Builder $query): void
    {
        parent::applySorting($query, 'name', 'asc');
    }

    private function applyDateRangeFilter(Builder $query): void
    {
        parent::applyDateRange($query, 'created_at');
    }
}
