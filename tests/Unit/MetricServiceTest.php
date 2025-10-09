<?php

declare(strict_types=1);

use App\Models\Metric;
use App\Models\User;
use App\Services\MetricService;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->metricService = new MetricService();
    $this->metric = Metric::factory()->create([
        'user_id' => $this->user->id,
        'date' => '2024-03-15',
        'weight' => 75.5,
        'note' => 'Test metric',
    ]);
});

describe('MetricService', function () {
    describe('getAll', function () {
        it('returns paginated metrics for user', function () {
            Metric::factory()->count(5)->create([
                'user_id' => $this->user->id,
            ]);

            $result = $this->metricService->getAll(['user_id' => $this->user->id]);

            expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
            expect($result->count())->toBe(6); // 5 + 1 from beforeEach
        });

        it('returns empty result when user_id is not provided', function () {
            $result = $this->metricService->getAll([]);

            expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
            expect($result->count())->toBe(0);
        });

        it('applies filters correctly', function () {
            Metric::factory()->create([
                'user_id' => $this->user->id,
                'date' => '2024-03-10',
                'weight' => 80.0,
            ]);

            $result = $this->metricService->getAll([
                'user_id' => $this->user->id,
                'date_from' => '2024-03-15',
            ]);

            expect($result->count())->toBe(1);
        });

        it('loads user relationship', function () {
            $result = $this->metricService->getAll(['user_id' => $this->user->id]);

            expect($result->first()->relationLoaded('user'))->toBeTrue();
        });
    });

    describe('getById', function () {
        it('returns metric by id', function () {
            $result = $this->metricService->getById($this->metric->id);

            expect($result->id)->toBe($this->metric->id);
            expect($result->relationLoaded('user'))->toBeTrue();
        });

        it('returns metric by id for specific user', function () {
            $result = $this->metricService->getById($this->metric->id, $this->user->id);

            expect($result->id)->toBe($this->metric->id);
        });

        it('throws exception for non-existent metric', function () {
            expect(fn() => $this->metricService->getById(999))
                ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });

        it('throws exception for metric belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherMetric = Metric::factory()->create([
                'user_id' => $otherUser->id,
            ]);

            expect(fn() => $this->metricService->getById($otherMetric->id, $this->user->id))
                ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('create', function () {
        it('creates new metric', function () {
            $data = [
                'user_id' => $this->user->id,
                'date' => '2024-03-20',
                'weight' => 76.0,
                'note' => 'New metric',
            ];

            $result = $this->metricService->create($data);

            expect($result)->toBeInstanceOf(Metric::class);
            expect($result->weight)->toBe('76.00');
            expect($result->note)->toBe('New metric');

            $this->assertDatabaseHas('metrics', [
                'user_id' => $this->user->id,
                'date' => '2024-03-20 00:00:00',
                'weight' => 76.0,
                'note' => 'New metric',
            ]);
        });
    });

    describe('update', function () {
        it('updates metric', function () {
            $data = [
                'weight' => 77.0,
                'note' => 'Updated metric',
            ];

            $result = $this->metricService->update($this->metric->id, $data);

            expect($result->weight)->toBe('77.00');
            expect($result->note)->toBe('Updated metric');
            expect($result->relationLoaded('user'))->toBeTrue();

            $this->assertDatabaseHas('metrics', [
                'id' => $this->metric->id,
                'weight' => '77.00',
                'note' => 'Updated metric',
            ]);
        });

        it('updates metric for specific user', function () {
            $data = ['weight' => 78.0];

            $result = $this->metricService->update($this->metric->id, $data, $this->user->id);

            expect($result->weight)->toBe('78.00');
        });

        it('throws exception for non-existent metric', function () {
            expect(fn() => $this->metricService->update(999, ['weight' => 80.0]))
                ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });

        it('throws exception for metric belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherMetric = Metric::factory()->create([
                'user_id' => $otherUser->id,
            ]);

            expect(fn() => $this->metricService->update($otherMetric->id, ['weight' => 80.0], $this->user->id))
                ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('delete', function () {
        it('deletes metric', function () {
            $result = $this->metricService->delete($this->metric->id);

            expect($result)->toBeTrue();

            $this->assertDatabaseMissing('metrics', [
                'id' => $this->metric->id,
            ]);
        });

        it('deletes metric for specific user', function () {
            $result = $this->metricService->delete($this->metric->id, $this->user->id);

            expect($result)->toBeTrue();
        });

        it('throws exception for non-existent metric', function () {
            expect(fn() => $this->metricService->delete(999))
                ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });

        it('throws exception for metric belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherMetric = Metric::factory()->create([
                'user_id' => $otherUser->id,
            ]);

            expect(fn() => $this->metricService->delete($otherMetric->id, $this->user->id))
                ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });
});
