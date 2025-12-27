<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Модель расшаренного цикла тренировок
 * 
 * Представляет расшаренный цикл тренировок, доступный для импорта другими пользователями.
 * 
 * @property int $id ID записи
 * @property int $cycle_id ID цикла
 * @property string $share_id UUID для доступа к расшаренному циклу
 * @property int $view_count Количество просмотров
 * @property int $import_count Количество импортов
 * @property bool $is_active Активна ли ссылка
 * @property \Carbon\Carbon|null $expires_at Дата истечения срока действия
 * @property \Carbon\Carbon $created_at Время создания записи
 * @property \Carbon\Carbon $updated_at Время последнего обновления записи
 * 
 * @property-read \App\Models\Cycle $cycle Цикл тренировок
 */
final class SharedCycle extends Model
{
    use HasFactory;

    protected $table = 'shared_cycles';

    protected $fillable = [
        'cycle_id',
        'share_id',
        'view_count',
        'import_count',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'view_count' => 'integer',
        'import_count' => 'integer',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Получить цикл тренировок
     * 
     * @return BelongsTo Связь с моделью Cycle
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    /**
     * Проверить, истек ли срок действия ссылки
     * 
     * @return bool True если срок действия истек
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Проверить, доступна ли ссылка для использования
     * 
     * @return bool True если ссылка активна и не истекла
     */
    public function isAccessible(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Инкрементировать счетчик просмотров
     * 
     * @return void
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Инкрементировать счетчик импортов
     * 
     * @return void
     */
    public function incrementImportCount(): void
    {
        $this->increment('import_count');
    }
}
