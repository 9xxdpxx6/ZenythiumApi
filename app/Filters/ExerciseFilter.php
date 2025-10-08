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
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
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
        if ($this->hasFilter('is_active')) {
            $query->where('is_active', $this->getFilter('is_active'));
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
