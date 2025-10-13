<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

final class CycleFilter extends BaseFilter
{
    public function apply(Builder $query): Builder
    {
        $this->applySearchFilter($query);
        $this->applyUserFilter($query);
        $this->applyDateRangeFilter($query);
        $this->applyWeeksFilter($query);
        $this->applySortingFilter($query);

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
            $query->where('user_id', $this->getFilter('user_id'));
        }
    }

    private function applyDateRangeFilter(Builder $query): void
    {
        if ($this->hasFilter('start_date_from')) {
            $query->where('start_date', '>=', $this->getFilter('start_date_from'));
        }

        if ($this->hasFilter('start_date_to')) {
            $query->where('start_date', '<=', $this->getFilter('start_date_to'));
        }

        if ($this->hasFilter('end_date_from')) {
            $query->where('end_date', '>=', $this->getFilter('end_date_from'));
        }

        if ($this->hasFilter('end_date_to')) {
            $query->where('end_date', '<=', $this->getFilter('end_date_to'));
        }
    }

    private function applyWeeksFilter(Builder $query): void
    {
        if ($this->hasFilter('weeks_min')) {
            $query->where('weeks', '>=', $this->getFilter('weeks_min'));
        }

        if ($this->hasFilter('weeks_max')) {
            $query->where('weeks', '<=', $this->getFilter('weeks_max'));
        }

        if ($this->hasFilter('weeks')) {
            $query->where('weeks', $this->getFilter('weeks'));
        }
    }

    private function applySortingFilter(Builder $query): void
    {
        // Если не указана кастомная сортировка, применяем дефолтную
        if (!$this->hasFilter('sort_by')) {
            $today = now()->format('Y-m-d');
            $query->orderByRaw("
                CASE 
                    WHEN (start_date IS NULL OR start_date <= '{$today}') 
                         AND (end_date IS NULL OR end_date >= '{$today}') 
                    THEN 0  -- Активные циклы
                    ELSE 1   -- Завершенные циклы
                END ASC,
                created_at DESC
            ");
        } else {
            // Применяем кастомную сортировку
            $sortBy = $this->getFilter('sort_by');
            $sortOrder = $this->getFilter('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
        }
    }
}
