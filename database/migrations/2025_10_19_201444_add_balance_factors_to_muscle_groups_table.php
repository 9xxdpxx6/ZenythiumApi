<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('muscle_groups', function (Blueprint $table) {
            $table->decimal('size_factor', 3, 2)->default(1.0)->after('name');
            $table->integer('optimal_frequency_per_week')->default(2)->after('size_factor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('muscle_groups', function (Blueprint $table) {
            $table->dropColumn(['size_factor', 'optimal_frequency_per_week']);
        });
    }
};
