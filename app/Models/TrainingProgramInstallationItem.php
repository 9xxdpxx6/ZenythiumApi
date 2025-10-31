<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TrainingProgramInstallationItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Модель элемента установки программы тренировок
 * 
 * Представляет элемент (упражнение, план или цикл), созданный при установке программы.
 * 
 * @property int $id ID элемента установки
 * @property int $training_program_installation_id ID установки программы
 * @property TrainingProgramInstallationItemType $item_type Тип элемента
 * @property int $item_id ID элемента
 * @property \Carbon\Carbon $created_at Время создания записи
 * @property \Carbon\Carbon $updated_at Время последнего обновления записи
 * 
 * @property-read \App\Models\TrainingProgramInstallation $trainingProgramInstallation Установка программы
 */
final class TrainingProgramInstallationItem extends Model
{
    use HasFactory;

    protected $table = 'training_program_installation_items';

    protected $fillable = [
        'training_program_installation_id',
        'item_type',
        'item_id',
    ];

    protected $casts = [
        'item_type' => TrainingProgramInstallationItemType::class,
    ];

    /**
     * Получить установку программы
     * 
     * @return BelongsTo Связь с моделью TrainingProgramInstallation
     */
    public function trainingProgramInstallation(): BelongsTo
    {
        return $this->belongsTo(TrainingProgramInstallation::class);
    }

    /**
     * Получить связанный элемент (упражнение, план или цикл)
     * 
     * @return Exercise|Plan|Cycle|null
     */
    public function item()
    {
        return match ($this->item_type) {
            TrainingProgramInstallationItemType::EXERCISE => $this->belongsTo(Exercise::class, 'item_id'),
            TrainingProgramInstallationItemType::PLAN => $this->belongsTo(Plan::class, 'item_id'),
            TrainingProgramInstallationItemType::CYCLE => $this->belongsTo(Cycle::class, 'item_id'),
        };
    }
}

