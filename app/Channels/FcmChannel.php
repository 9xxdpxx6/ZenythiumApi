<?php

declare(strict_types=1);

namespace App\Channels;

use App\Models\UserDeviceToken;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Канал для отправки FCM push-уведомлений
 * 
 * Поддерживает как V1 API (с Service Account), так и Legacy API (с Server Key)
 */
final class FcmChannel
{
    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @return void
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toFcm')) {
            return;
        }

        /** @var \App\Notifications\GoalAchievedNotification|\App\Notifications\GoalProgressNotification|\App\Notifications\GoalDeadlineReminderNotification|\App\Notifications\GoalFailedNotification $notification */
        $fcmData = $notification->toFcm($notifiable);
        $deviceTokens = $notifiable->deviceTokens()->pluck('device_token')->toArray();

        if (empty($deviceTokens)) {
            return;
        }

        $useV1Api = config('services.fcm.use_v1_api', true);

        if ($useV1Api) {
            $this->sendViaV1Api($deviceTokens, $fcmData);
        } else {
            $this->sendViaLegacyApi($deviceTokens, $fcmData);
        }
    }

    /**
     * Отправка через V1 API (с Service Account)
     *
     * @param array<string> $deviceTokens
     * @param array<string, mixed> $fcmData
     * @return void
     */
    private function sendViaV1Api(array $deviceTokens, array $fcmData): void
    {
        $serviceAccountPath = config('services.fcm.service_account_path');
        $projectId = config('services.fcm.project_id');

        if (!$serviceAccountPath || !file_exists($serviceAccountPath)) {
            Log::error('FCM Service Account file not found', [
                'path' => $serviceAccountPath,
            ]);
            return;
        }

        if (!$projectId) {
            Log::error('FCM Project ID not configured');
            return;
        }

        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
        if (!$serviceAccount) {
            Log::error('FCM Service Account file is invalid');
            return;
        }

        // Получаем OAuth 2.0 access token
        $accessToken = $this->getAccessToken($serviceAccount);
        if (!$accessToken) {
            Log::error('Failed to get FCM access token');
            return;
        }

        // Отправляем уведомление на каждое устройство
        foreach ($deviceTokens as $token) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $fcmData['title'] ?? '',
                            'body' => $fcmData['body'] ?? '',
                        ],
                        'data' => array_map('strval', $fcmData['data'] ?? []),
                    ],
                ]);

                if (!$response->successful()) {
                    Log::warning('FCM V1 notification failed', [
                        'token' => substr($token, 0, 20) . '...',
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);

                    // Если токен невалидный, удаляем его
                    $responseData = $response->json();
                    if (isset($responseData['error']['code']) && in_array($responseData['error']['code'], ['NOT_FOUND', 'INVALID_ARGUMENT'])) {
                        UserDeviceToken::where('device_token', $token)->delete();
                    }
                }
            } catch (\Exception $e) {
                Log::error('FCM V1 notification error', [
                    'token' => substr($token, 0, 20) . '...',
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Отправка через Legacy API (с Server Key)
     *
     * @param array<string> $deviceTokens
     * @param array<string, mixed> $fcmData
     * @return void
     */
    private function sendViaLegacyApi(array $deviceTokens, array $fcmData): void
    {
        $serverKey = config('services.fcm.server_key');
        if (!$serverKey) {
            Log::warning('FCM server key not configured');
            return;
        }

        // Отправляем уведомление на каждое устройство
        foreach ($deviceTokens as $token) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'key=' . $serverKey,
                    'Content-Type' => 'application/json',
                ])->post('https://fcm.googleapis.com/fcm/send', [
                    'to' => $token,
                    'notification' => [
                        'title' => $fcmData['title'] ?? '',
                        'body' => $fcmData['body'] ?? '',
                    ],
                    'data' => $fcmData['data'] ?? [],
                ]);

                if (!$response->successful()) {
                    Log::warning('FCM Legacy notification failed', [
                        'token' => substr($token, 0, 20) . '...',
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);

                    // Если токен невалидный, удаляем его
                    if ($response->status() === 400 || $response->status() === 404) {
                        UserDeviceToken::where('device_token', $token)->delete();
                    }
                }
            } catch (\Exception $e) {
                Log::error('FCM Legacy notification error', [
                    'token' => substr($token, 0, 20) . '...',
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Получить OAuth 2.0 access token для V1 API
     *
     * @param array<string, mixed> $serviceAccount
     * @return string|null
     */
    private function getAccessToken(array $serviceAccount): ?string
    {
        $now = time();
        $jwt = $this->createJWT($serviceAccount, $now);
        
        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'] ?? null;
            }

            Log::error('Failed to get OAuth token', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('OAuth token request error', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Создать JWT для OAuth 2.0 запроса
     *
     * @param array<string, mixed> $serviceAccount
     * @param int $now
     * @return string
     */
    private function createJWT(array $serviceAccount, int $now): string
    {
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signatureInput = $headerEncoded . '.' . $payloadEncoded;
        $signature = '';
        
        $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
        if (!$privateKey) {
            throw new \RuntimeException('Invalid private key in service account');
        }

        openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKey);

        $signatureEncoded = $this->base64UrlEncode($signature);

        return $signatureInput . '.' . $signatureEncoded;
    }

    /**
     * Base64 URL encode
     *
     * @param string $data
     * @return string
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

