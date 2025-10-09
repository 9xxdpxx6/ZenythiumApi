<?php

declare(strict_types=1);

use App\Models\Metric;
use App\Models\User;
use App\Filters\MetricFilter;
use Illuminate\Database\Eloquent\Builder;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->metric = Metric::factory()->create([
        'user_id' => $this->user->id,
        'date' => '2024-03-15',
        'weight' => 75.5,
        'note' => 'Test metric',
    ]);
});

describe('MetricFilter', function () {
    describe('apply', function () {
        it('applies search filter', function () {
            Metric::factory()->create([
                'user_id' => $this->user->id,
                'note' => 'Different note',
            ]);

            $filter = new MetricFilter(['search' => 'Test']);
            $query = Metric::query();
            $filter->apply($query);

            $results = $query->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->note)->toBe('Test metric');
        });

        it('applies user filter', function () {
            $otherUser = User::factory()->create();
            Metric::factory()->create([
                'user_id' => $otherUser->id,
                'note' => 'Other user metric',
            ]);

            $filter = new MetricFilter(['user_id' => $this->user->id]);
            $query = Metric::query();
            $filter->apply($query);

            $results = $query->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->user_id)->toBe($this->user->id);
        });

        it('applies date range filter', function () {
            Metric::factory()->create([
                'user_id' => $this->user->id,
                'date' => '2024-03-10',
            ]);

            Metric::factory()->create([
                'user_id' => $this->user->id,
                'date' => '2024-03-20',
            ]);

            $filter = new MetricFilter([
                'date_from' => '2024-03-15',
                'date_to' => '2024-03-20',
            ]);
            $query = Metric::query();
            $filter->apply($query);

            $results = $query->get();

            // Should include: beforeEach metric (2024-03-15) + new metric (2024-03-20) = 2 results
            expect($results)->toHaveCount(2);
            
            // Verify the dates are correct
            $dates = $results->pluck('date')->map(fn($date) => $date->format('Y-m-d'))->toArray();
            expect($dates)->toContain('2024-03-15');
            expect($dates)->toContain('2024-03-20');
        });

        it('applies weight range filter', function () {
            Metric::factory()->create([
                'user_id' => $this->user->id,
                'weight' => 70.0,
            ]);

            Metric::factory()->create([
                'user_id' => $this->user->id,
                'weight' => 80.0,
            ]);

            $filter = new MetricFilter([
                'weight_from' => 75.0,
                'weight_to' => 80.0,
            ]);
            $query = Metric::query();
            $filter->apply($query);

            $results = $query->get();

            // Should include: beforeEach metric (75.5) + new metric (80.0) = 2 results
            expect($results)->toHaveCount(2);
        });

        it('applies sorting by date desc by default', function () {
            Metric::factory()->create([
                'user_id' => $this->user->id,
                'date' => '2024-03-10',
            ]);

            Metric::factory()->create([
                'user_id' => $this->user->id,
                'date' => '2024-03-20',
            ]);

            $filter = new MetricFilter([]);
            $query = Metric::query();
            $filter->apply($query);

            $results = $query->get();

            expect($results->first()->date->format('Y-m-d'))->toBe('2024-03-20');
            expect($results->last()->date->format('Y-m-d'))->toBe('2024-03-10');
        });

        it('applies custom sorting', function () {
            Metric::factory()->create([
                'user_id' => $this->user->id,
                'date' => '2024-03-10',
            ]);

            Metric::factory()->create([
                'user_id' => $this->user->id,
                'date' => '2024-03-20',
            ]);

            $filter = new MetricFilter(['sort_by' => 'date', 'sort_order' => 'asc']);
            $query = Metric::query();
            $filter->apply($query);

            $results = $query->get();

            expect($results->first()->date->format('Y-m-d'))->toBe('2024-03-10');
            expect($results->last()->date->format('Y-m-d'))->toBe('2024-03-20');
        });

        it('combines multiple filters', function () {
            Metric::factory()->create([
                'user_id' => $this->user->id,
                'date' => '2024-03-10',
                'weight' => 80.0,
                'note' => 'Different note',
            ]);

            $filter = new MetricFilter([
                'user_id' => $this->user->id,
                'date_from' => '2024-03-15',
                'weight_to' => 76.0,
                'search' => 'Test',
            ]);
            $query = Metric::query();
            $filter->apply($query);

            $results = $query->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->note)->toBe('Test metric');
        });

        it('handles empty filters', function () {
            $filter = new MetricFilter([]);
            $query = Metric::query();
            $filter->apply($query);

            $results = $query->get();

            expect($results)->toHaveCount(1);
        });
    });
});
