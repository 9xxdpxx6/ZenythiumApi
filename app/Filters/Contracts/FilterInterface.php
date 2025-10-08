<?php

declare(strict_types=1);

namespace App\Filters\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface FilterInterface
{
    public function apply(Builder $query): Builder;
}
