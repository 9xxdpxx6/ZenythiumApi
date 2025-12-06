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
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('goal_achieved_enabled')->default(true);
            $table->boolean('goal_progress_enabled')->default(true);
            $table->boolean('goal_deadline_reminder_enabled')->default(true);
            $table->boolean('goal_failed_enabled')->default(true);
            $table->json('goal_progress_milestones')->nullable();
            $table->json('goal_deadline_reminder_days')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
