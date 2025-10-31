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

    /**
     * Умный поиск по словам: разбивает поисковую строку на слова
     * и ищет записи, содержащие все слова (в любом порядке).
     * 
     * Пример: "жим лежа" найдет "жим штанги лежа", "лежа жим" и т.д.
     * 
     * @param Builder $query
     * @param array<string> $fields Массив полей для поиска
     * @param string $searchTerm Поисковая строка
     * @param int $minWordLength Минимальная длина слова для поиска
     */
    protected function applySmartSearch(Builder $query, array $fields, string $searchTerm, int $minWordLength = 2): void
    {
        if (empty($searchTerm) || empty($fields)) {
            return;
        }

        // Разбиваем строку на слова, убираем пробелы и пустые значения
        $words = array_filter(
            array_map('trim', explode(' ', $searchTerm)),
            fn(string $word): bool => mb_strlen($word) >= $minWordLength
        );

        if (empty($words)) {
            return;
        }

        $query->where(function ($q) use ($words, $fields): void {
            foreach ($fields as $field) {
                $q->orWhere(function ($fieldQuery) use ($words, $field): void {
                    foreach ($words as $word) {
                        // Экранируем специальные символы для LIKE
                        $escapedWord = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $word);
                        $fieldQuery->where($field, 'like', '%' . $escapedWord . '%');
                    }
                });
            }
        });
    }

    /**
     * Умный поиск по словам для отношений (whereHas).
     * 
     * @param Builder $query
     * @param string $relation Название отношения
     * @param array<string> $fields Массив полей для поиска в отношении
     * @param string $searchTerm Поисковая строка
     * @param int $minWordLength Минимальная длина слова для поиска
     */
    protected function applySmartSearchInRelation(
        Builder $query,
        string $relation,
        array $fields,
        string $searchTerm,
        int $minWordLength = 2
    ): void {
        if (empty($searchTerm) || empty($fields)) {
            return;
        }

        // Разбиваем строку на слова
        $words = array_filter(
            array_map('trim', explode(' ', $searchTerm)),
            fn(string $word): bool => mb_strlen($word) >= $minWordLength
        );

        if (empty($words)) {
            return;
        }

        $query->whereHas($relation, function ($relationQuery) use ($words, $fields): void {
            foreach ($fields as $field) {
                $relationQuery->orWhere(function ($fieldQuery) use ($words, $field): void {
                    foreach ($words as $word) {
                        // Экранируем специальные символы для LIKE
                        $escapedWord = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $word);
                        $fieldQuery->where($field, 'like', '%' . $escapedWord . '%');
                    }
                });
            }
        });
    }

    /**
     * Умный поиск по словам для отношений с OR логикой (orWhereHas).
     * Используется для объединения нескольких условий поиска через OR.
     * 
     * @param Builder $query
     * @param string $relation Название отношения
     * @param array<string> $fields Массив полей для поиска в отношении
     * @param string $searchTerm Поисковая строка
     * @param int $minWordLength Минимальная длина слова для поиска
     */
    protected function applySmartSearchInRelationOr(
        Builder $query,
        string $relation,
        array $fields,
        string $searchTerm,
        int $minWordLength = 2
    ): void {
        if (empty($searchTerm) || empty($fields)) {
            return;
        }

        // Разбиваем строку на слова
        $words = array_filter(
            array_map('trim', explode(' ', $searchTerm)),
            fn(string $word): bool => mb_strlen($word) >= $minWordLength
        );

        if (empty($words)) {
            return;
        }

        $query->orWhereHas($relation, function ($relationQuery) use ($words, $fields): void {
            foreach ($fields as $field) {
                $relationQuery->orWhere(function ($fieldQuery) use ($words, $field): void {
                    foreach ($words as $word) {
                        // Экранируем специальные символы для LIKE
                        $escapedWord = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $word);
                        $fieldQuery->where($field, 'like', '%' . $escapedWord . '%');
                    }
                });
            }
        });
    }

    /**
     * Умный поиск по словам с OR логикой (orWhere).
     * Используется для объединения нескольких условий поиска через OR.
     * 
     * @param Builder $query
     * @param array<string> $fields Массив полей для поиска
     * @param string $searchTerm Поисковая строка
     * @param int $minWordLength Минимальная длина слова для поиска
     */
    protected function applySmartSearchOr(
        Builder $query,
        array $fields,
        string $searchTerm,
        int $minWordLength = 2
    ): void {
        if (empty($searchTerm) || empty($fields)) {
            return;
        }

        // Разбиваем строку на слова
        $words = array_filter(
            array_map('trim', explode(' ', $searchTerm)),
            fn(string $word): bool => mb_strlen($word) >= $minWordLength
        );

        if (empty($words)) {
            return;
        }

        $query->orWhere(function ($q) use ($words, $fields): void {
            foreach ($fields as $field) {
                $q->orWhere(function ($fieldQuery) use ($words, $field): void {
                    foreach ($words as $word) {
                        // Экранируем специальные символы для LIKE
                        $escapedWord = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $word);
                        $fieldQuery->where($field, 'like', '%' . $escapedWord . '%');
                    }
                });
            }
        });
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

    protected function getFilter(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
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
