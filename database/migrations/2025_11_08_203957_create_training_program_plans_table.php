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
        Schema::create('training_program_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_program_cycle_id')->constrained('training_program_cycles')->cascadeOnDelete();
            $table->string('name');
            $table->integer('order')->default(1);
            $table->timestamps();

            $table->index(['training_program_cycle_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_program_plans');
    }
};
