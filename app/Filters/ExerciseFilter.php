<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

final class ExerciseFilter extends BaseFilter
{
    public function apply(Builder $query): Builder
    {
        $this->applySearchFilter($query);
        $this->applyUserFilter($query);
        $this->applyMuscleGroupFilter($query);
        $this->applyActiveFilter($query);
        $this->applySortingFilter($query);
        $this->applyDateRangeFilter($query);

        return $query;
    }

    private function applySearchFilter(Builder $query): void
    {
        if ($this->hasFilter('search')) {
            $searchTerm = $this->getFilter('search');
            $this->applySmartSearch($query, ['name', 'description'], $searchTerm);
        }
    }

    private function applyUserFilter(Builder $query): void
    {
        if ($this->hasFilter('user_id')) {
            $query->where('user_id', $this->getFilter('user_id'));
        }
    }

    private function applyMuscleGroupFilter(Builder $query): void
    {
        if ($this->hasFilter('muscle_group_id')) {
            $query->where('muscle_group_id', $this->getFilter('muscle_group_id'));
        }
    }

    private function applyActiveFilter(Builder $query): void
    {
        if (isset($this->filters['is_active']) && $this->filters['is_active'] !== '') {
            $isActive = $this->filters['is_active'];
            // Преобразуем строковые значения в boolean
            if ($isActive === '1' || $isActive === 1 || $isActive === true) {
                $query->where('is_active', true);
            } elseif ($isActive === '0' || $isActive === 0 || $isActive === false) {
                $query->where('is_active', false);
            }
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
