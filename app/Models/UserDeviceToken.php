<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель токенов устройств для push-уведомлений
 *
 * @property int $id ID записи
 * @property int $user_id ID пользователя
 * @property string $device_token Токен устройства (FCM token)
 * @property string $platform Платформа (ios, android)
 * @property string|null $device_id ID устройства (опционально)
 * @property \Carbon\Carbon $created_at Время создания записи
 * @property \Carbon\Carbon $updated_at Время последнего обновления записи
 *
 * @property-read \App\Models\User $user Пользователь
 */
final class UserDeviceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_token',
        'platform',
        'device_id',
    ];

    protected $casts = [
        'platform' => 'string',
    ];

    /**
     * Получить пользователя
     *
     * @return BelongsTo Связь с моделью User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
