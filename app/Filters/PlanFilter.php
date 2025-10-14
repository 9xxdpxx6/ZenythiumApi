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
                $q->where('name', 'like', '%' . $searchTerm . '%');
            });
        }
    }

    private function applyUserFilter(Builder $query): void
    {
        if ($this->hasFilter('user_id')) {
            $userId = $this->getFilter('user_id');
            $standalone = $this->getFilter('standalone');
            
            // Если фильтр standalone не указан, показываем все планы пользователя (включая standalone)
            if (!$this->hasFilter('standalone')) {
                $query->where(function ($q) use ($userId) {
                    $q->whereHas('cycle', function ($cycleQuery) use ($userId) {
                        $cycleQuery->where('user_id', $userId);
                    })->orWhereNull('cycle_id');
                });
            } else {
                // Если указан фильтр standalone, применяем его логику
                if ($standalone === true || $standalone === 'true' || $standalone === '1') {
                    // Только standalone планы (без цикла)
                    $query->whereNull('cycle_id');
                } elseif ($standalone === false || $standalone === 'false' || $standalone === '0') {
                    // Только планы с циклом, принадлежащие пользователю
                    $query->whereHas('cycle', function ($cycleQuery) use ($userId) {
                        $cycleQuery->where('user_id', $userId);
                    });
                }
            }
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

    private function applyActiveFilter(Builder $query): void
    {
        if ($this->hasFilter('is_active')) {
            $query->where('is_active', $this->getFilter('is_active'));
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
