<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

final class WorkoutFilter extends BaseFilter
{
    public function apply(Builder $query): Builder
    {
        $this->applySearchFilter($query);
        $this->applyUserFilter($query);
        $this->applyPlanFilter($query);
        $this->applyDateRangeFilter($query);
        $this->applyCompletionFilter($query);
        $this->applySortingFilter($query);

        return $query;
    }

    private function applySearchFilter(Builder $query): void
    {
        if ($this->hasFilter('search')) {
            $searchTerm = $this->getFilter('search');
            $words = array_filter(
                array_map('trim', explode(' ', $searchTerm)),
                fn(string $word): bool => mb_strlen($word) >= 2
            );
            
            if (empty($words)) {
                return;
            }
            
            $query->where(function ($q) use ($words): void {
                // Поиск по имени плана
                $q->whereHas('plan', function ($relationQuery) use ($words): void {
                    $relationQuery->where(function ($fieldQuery) use ($words): void {
                        foreach ($words as $word) {
                            $escapedWord = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $word);
                            $fieldQuery->where('name', 'like', '%' . $escapedWord . '%');
                        }
                    });
                });
                
                // ИЛИ поиск по имени пользователя
                $q->orWhereHas('user', function ($relationQuery) use ($words): void {
                    $relationQuery->where(function ($fieldQuery) use ($words): void {
                        foreach ($words as $word) {
                            $escapedWord = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $word);
                            $fieldQuery->where('name', 'like', '%' . $escapedWord . '%');
                        }
                    });
                });
            });
        }
    }

    private function applyUserFilter(Builder $query): void
    {
        if ($this->hasFilter('user_id')) {
            $query->where('user_id', $this->getFilter('user_id'));
        }
    }

    private function applyPlanFilter(Builder $query): void
    {
        if ($this->hasFilter('plan_id')) {
            $query->where('plan_id', $this->getFilter('plan_id'));
        }
    }

    private function applyDateRangeFilter(Builder $query): void
    {
        if ($this->hasFilter('started_at_from')) {
            $query->where('started_at', '>=', $this->getFilter('started_at_from'));
        }

        if ($this->hasFilter('started_at_to')) {
            $query->where('started_at', '<=', $this->getFilter('started_at_to'));
        }

        if ($this->hasFilter('finished_at_from')) {
            $query->where('finished_at', '>=', $this->getFilter('finished_at_from'));
        }

        if ($this->hasFilter('finished_at_to')) {
            $query->where('finished_at', '<=', $this->getFilter('finished_at_to'));
        }
    }

    private function applyCompletionFilter(Builder $query): void
    {
        if ($this->hasFilter('completed')) {
            $completed = $this->getFilter('completed');
            if ($completed === 'true' || $completed === true || $completed === '1') {
                $query->whereNotNull('finished_at');
            } elseif ($completed === 'false' || $completed === false || $completed === '0') {
                $query->whereNull('finished_at');
            }
        }
    }

    private function applySortingFilter(Builder $query): void
    {
        parent::applySorting($query, 'started_at', 'desc');
    }
}
