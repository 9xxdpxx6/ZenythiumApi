<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Matches the refresh-token grace period in AuthController::refreshToken() —
// tokens rejected there are also no longer needed in the database.
Schedule::command('sanctum:prune-expired --hours=168')->daily();
