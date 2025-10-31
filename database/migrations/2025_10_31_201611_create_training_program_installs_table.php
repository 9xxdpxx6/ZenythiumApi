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
        Schema::create('training_program_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installed_cycle_id')->nullable()->constrained('cycles')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'training_program_id']);
            $table->index('installed_cycle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_program_installations');
    }
};
