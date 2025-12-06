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
        Schema::create('goal_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->string('notification_type'); // achieved, progress, deadline_reminder, failed
            $table->integer('milestone')->nullable(); // 25, 50, 75, 90 для progress
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index('goal_id');
            $table->index(['goal_id', 'notification_type', 'milestone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goal_notifications');
    }
};
