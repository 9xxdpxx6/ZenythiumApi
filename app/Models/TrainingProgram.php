<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель программы тренировок
 * 
 * Представляет программу тренировок из каталога, которую пользователи могут устанавливать.
 * 
 * @property int $id ID программы
 * @property string $name Название программы
 * @property string|null $description Описание программы
 * @property int|null $author_id ID автора программы (пользователя)
 * @property int $duration_weeks Продолжительность программы в неделях
 * @property bool $is_active Активна ли программа
 * @property \Carbon\Carbon $created_at Время создания записи
 * @property \Carbon\Carbon $updated_at Время последнего обновления записи
 * 
 * @property-read \App\Models\User|null $author Автор программы
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TrainingProgramInstallation[] $installs Установки программы
 */
final class TrainingProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'author_id',
        'duration_weeks',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration_weeks' => 'integer',
    ];

    /**
     * Получить автора программы
     * 
     * @return BelongsTo Связь с моделью User
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Получить установки программы
     * 
     * @return HasMany Связь с коллекцией TrainingProgramInstallation
     */
    public function installs(): HasMany
    {
        return $this->hasMany(TrainingProgramInstallation::class);
    }
}
