<?php

use App\Enums\ExerciseSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Добавляет колонку source (enum) для трекинга источника создания упражнения.
     * 
     * Значения:
     *   - null        → упражнение создано вручную пользователем
     *   - 'base_pack' → установлено из базового пакета упражнений
     * 
     * Колонка используется для:
     *   - Проверки статуса установки пакета
     *   - Отката (uninstall) — удаления всех упражнений из пакета
     *   - Предотвращения повторной установки
     */
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            // SQLite не поддерживает ENUM — используем string как fallback
            if (DB::getDriverName() === 'sqlite') {
                $table->string('source', 50)->nullable()->default(null)->after('is_active');
            } else {
                $table->enum('source', ExerciseSource::values())
                    ->nullable()
                    ->default(null)
                    ->after('is_active')
                    ->comment('Источник создания: null=вручную, base_pack=базовый пакет');
            }

            $table->index(['user_id', 'source'], 'exercises_user_source_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropIndex('exercises_user_source_idx');
            $table->dropColumn('source');
        });
    }
};
