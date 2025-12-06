<?php

use App\Enums\GoalStatus;
use App\Enums\GoalType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // GoalType enum
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('target_value', 10, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->foreignId('exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active'); // GoalStatus enum
            $table->decimal('current_value', 10, 2)->nullable();
            $table->integer('progress_percentage')->default(0);
            $table->integer('last_notified_milestone')->nullable();
            $table->timestamp('last_deadline_reminder_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('achieved_value', 10, 2)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['type', 'status']);
            $table->index('end_date');
            $table->index(['user_id', 'status', 'completed_at']);
        });

        $this->addTypeCheckConstraint();
        $this->addStatusCheckConstraint();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goals');
    }

    /**
     * Добавить CHECK constraint для проверки допустимых значений type
     * SQLite не поддерживает CHECK constraints через ALTER TABLE, поэтому пропускаем для SQLite
     */
    private function addTypeCheckConstraint(): void
    {
        // SQLite не поддерживает ALTER TABLE ADD CONSTRAINT для CHECK constraints
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $allowedValues = array_map(
            fn(string $value): string => DB::getPdo()->quote($value),
            GoalType::values()
        );

        $valuesList = implode(', ', $allowedValues);
        
        DB::statement(
            "ALTER TABLE goals 
             ADD CONSTRAINT check_goal_type 
             CHECK (type IN ({$valuesList}))"
        );
    }

    /**
     * Добавить CHECK constraint для проверки допустимых значений status
     * SQLite не поддерживает CHECK constraints через ALTER TABLE, поэтому пропускаем для SQLite
     */
    private function addStatusCheckConstraint(): void
    {
        // SQLite не поддерживает ALTER TABLE ADD CONSTRAINT для CHECK constraints
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $allowedValues = array_map(
            fn(string $value): string => DB::getPdo()->quote($value),
            GoalStatus::values()
        );

        $valuesList = implode(', ', $allowedValues);
        
        DB::statement(
            "ALTER TABLE goals 
             ADD CONSTRAINT check_goal_status 
             CHECK (status IN ({$valuesList}))"
        );
    }
};
