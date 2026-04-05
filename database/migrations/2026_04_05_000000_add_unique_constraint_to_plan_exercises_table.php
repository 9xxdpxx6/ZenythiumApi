<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Удаляем дубликаты plan_exercises, оставляя запись с максимальным id
        // Кросс-платформенный способ (MySQL, SQLite, PostgreSQL)
        $duplicates = DB::table('plan_exercises')
            ->select('plan_id', 'exercise_id')
            ->groupBy('plan_id', 'exercise_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $keepId = DB::table('plan_exercises')
                ->where('plan_id', $dup->plan_id)
                ->where('exercise_id', $dup->exercise_id)
                ->max('id');

            DB::table('plan_exercises')
                ->where('plan_id', $dup->plan_id)
                ->where('exercise_id', $dup->exercise_id)
                ->where('id', '<', $keepId)
                ->delete();
        }

        Schema::table('plan_exercises', function ($table) {
            $table->unique(['plan_id', 'exercise_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_exercises', function (Blueprint $table) {
            $table->dropUnique(['plan_id', 'exercise_id']);
        });
    }
};
