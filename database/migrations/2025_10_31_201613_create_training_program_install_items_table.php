<?php

use App\Enums\TrainingProgramInstallationItemType;
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
        Schema::create('training_program_installation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('training_program_installation_id');
            $table->string('item_type');
            $table->unsignedBigInteger('item_id');
            $table->timestamps();

            $table->foreign('training_program_installation_id', 'tpii_tpi_id_fk')
                ->references('id')
                ->on('training_program_installations')
                ->onDelete('cascade');

            $table->index(['training_program_installation_id', 'item_type'], 'tpii_tpi_id_type_idx');
            $table->index(['item_type', 'item_id'], 'tpii_type_id_idx');
        });

        $this->addItemTypeCheckConstraint();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // CHECK constraint удалится автоматически вместе с таблицей
        Schema::dropIfExists('training_program_installation_items');
    }

    /**
     * Добавить CHECK constraint для проверки допустимых значений item_type
     */
    private function addItemTypeCheckConstraint(): void
    {
        $allowedValues = array_map(
            fn(string $value): string => DB::getPdo()->quote($value),
            TrainingProgramInstallationItemType::values()
        );

        $valuesList = implode(', ', $allowedValues);
        
        DB::statement(
            "ALTER TABLE training_program_installation_items 
             ADD CONSTRAINT check_item_type 
             CHECK (item_type IN ({$valuesList}))"
        );
    }

};
