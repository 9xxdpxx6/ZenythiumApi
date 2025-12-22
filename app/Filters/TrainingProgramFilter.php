<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

final class TrainingProgramFilter extends BaseFilter
{
    public function apply(Builder $query): Builder
    {
        $this->applySearchFilter($query);
        $this->applyActiveFilter($query);
        $this->applySortingFilter($query);

        return $query;
    }

    private function applySearchFilter(Builder $query): void
    {
        if ($this->hasFilter('search')) {
            $searchTerm = $this->getFilter('search');
            $this->applySmartSearch($query, ['name', 'description'], $searchTerm);
        }
    }

    private function applyActiveFilter(Builder $query): void
    {
        // Проверяем наличие ключа, даже если значение может быть "0"
        if (isset($this->filters['is_active']) && $this->filters['is_active'] !== '' && $this->filters['is_active'] !== null) {
            $isActive = $this->getFilter('is_active');
            // Преобразуем строку "0"/"1" в boolean
            $isActiveBool = filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isActiveBool !== null) {
                $query->where('is_active', $isActiveBool);
            } else {
                // Если не boolean, пробуем напрямую
                $query->where('is_active', (bool)$isActive);
            }
        }
    }

    private function applySortingFilter(Builder $query): void
    {
        // По умолчанию сортируем по дате создания (новые сначала)
        $this->applySorting($query, 'created_at', 'desc');
    }
}

