<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class SmartCaptchaService
{
    private const VERIFY_URL = 'https://smartcaptcha.yandexcloud.net/validate';

    private readonly ?string $serverKey;

    public function __construct(?string $serverKey = null)
    {
        $this->serverKey = $serverKey ?? config('services.yandex_smartcaptcha.server_key') ?? env('YANDEX_SMARTCAPTCHA_SERVER_KEY');
    }

    /**
     * Проверить токен SmartCaptcha
     * 
     * @param string $token Токен от фронтенда
     * @param string|null $ip IP адрес пользователя (опционально)
     * @return bool True если токен валидный, false если нет
     */
    public function verify(string $token, ?string $ip = null): bool
    {
        if (empty($this->serverKey)) {
            Log::warning('Yandex SmartCaptcha Server Key не настроен');
            return false;
        }

        if (empty($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->post(self::VERIFY_URL, [
                'secret' => $this->serverKey,
                'token' => $token,
                'ip' => $ip,
            ]);

            $data = $response->json();

            // Проверяем статус ответа
            if ($response->successful() && isset($data['status']) && $data['status'] === 'ok') {
                return true;
            }

            Log::warning('SmartCaptcha verification failed', [
                'response' => $data,
                'token' => substr($token, 0, 20) . '...',
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('SmartCaptcha verification error', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...',
            ]);

            return false;
        }
    }
}

