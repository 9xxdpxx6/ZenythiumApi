<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\SmartCaptchaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    // SmartCaptchaService is final — мокаем HTTP-вызов к Yandex SmartCaptcha API
    Http::fake([
        'smartcaptcha.yandexcloud.net/*' => Http::response(['status' => 'ok'], 200),
    ]);

    // Биндим реальный сервис с тестовым ключом (без ключа verify() вернёт false)
    app()->instance(SmartCaptchaService::class, new SmartCaptchaService('test-server-key'));
});

describe('Authentication', function () {
    describe('Registration', function () {
        it('allows user to register', function () {
            $userData = [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'smartcaptcha_token' => 'test-token',
            ];

            $response = $this->postJson('/api/v1/register', $userData);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'created_at',
                        'updated_at',
                    ],
                    'token',
                    'token_type',
                ]);

            $this->assertDatabaseHas('users', [
                'email' => 'test@example.com',
            ]);
        });

        it('allows registration without captcha when SmartCaptcha is not required', function () {
            config(['services.yandex_smartcaptcha.required' => false]);

            $userData = [
                'name' => 'Local User',
                'email' => 'local@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ];

            $response = $this->postJson('/api/v1/register', $userData);

            $response->assertStatus(201);
            $this->assertDatabaseHas('users', [
                'email' => 'local@example.com',
            ]);
        });

        it('prevents registration with duplicate email', function () {
            // Создаем существующего пользователя
            User::factory()->create([
                'email' => 'existing@example.com',
            ]);

            // Пытаемся зарегистрироваться с тем же email
            $userData = [
                'name' => 'Duplicate User',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'smartcaptcha_token' => 'test-token',
            ];

            $response = $this->postJson('/api/v1/register', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email'])
                ->assertJson([
                    'message' => 'Ошибка валидации',
                ]);

            // Проверяем, что второй пользователь не создан
            $this->assertDatabaseCount('users', 1);

            // Проверяем точный формат ответа для мобильного приложения
            $responseData = $response->json();
            expect($responseData)->toHaveKey('message');
            expect($responseData)->toHaveKey('errors');
            expect($responseData['errors'])->toHaveKey('email');
            expect($responseData['errors']['email'])->toBeArray();
            expect($responseData['errors']['email'][0])->toBeString();

            // Проверяем, что сообщение на русском языке
            expect($responseData['errors']['email'][0])->toBe('Пользователь с таким email уже зарегистрирован.');
        });
    });

    describe('Login', function () {
        it('allows user to login', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => Hash::make('password123'),
            ]);

            $loginData = [
                'email' => 'test@example.com',
                'password' => 'password123',
            ];

            $response = $this->postJson('/api/v1/login', $loginData);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'created_at',
                        'updated_at',
                    ],
                    'token',
                    'token_type',
                ]);
        });
    });

    describe('Logout', function () {
        it('allows user to logout', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->postJson('/api/v1/logout');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => null,
                    'message' => 'Выход выполнен успешно',
                ]);
        });
    });

    describe('Profile', function () {
        it('allows user to get profile', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->getJson('/api/v1/user');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'email',
                        'created_at',
                        'updated_at',
                    ],
                    'message',
                ]);
        });

        it('allows user to update profile', function () {
            $user = User::factory()->create([
                'name' => 'Old Name',
                'email' => 'test@example.com',
            ]);
            $token = $user->createToken('test-token')->plainTextToken;

            $profileData = [
                'name' => 'New Nickname',
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->putJson('/api/v1/user', $profileData);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'email',
                        'created_at',
                        'updated_at',
                    ],
                    'message',
                ])
                ->assertJson([
                    'data' => [
                        'id' => $user->id,
                        'name' => 'New Nickname',
                        'email' => 'test@example.com',
                    ],
                    'message' => 'Профиль успешно обновлен',
                ]);

            // Проверяем, что имя обновилось в базе данных
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'name' => 'New Nickname',
                'email' => 'test@example.com',
            ]);

            // Проверяем, что email не изменился
            expect($response->json('data.email'))->toBe('test@example.com');
        });

        it('validates name is required', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->putJson('/api/v1/user', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name'])
                ->assertJson([
                    'message' => 'Ошибка валидации',
                ]);

            // Проверяем точный формат ответа
            $responseData = $response->json();
            expect($responseData)->toHaveKey('errors');
            expect($responseData['errors'])->toHaveKey('name');
            expect($responseData['errors']['name'])->toBeArray();
            expect($responseData['errors']['name'][0])->toBe('Имя пользователя обязательно.');
        });

        it('validates name is string', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->putJson('/api/v1/user', [
                'name' => 12345,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('validates name max length', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            $longName = str_repeat('a', 256); // 256 символов - больше лимита

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->putJson('/api/v1/user', [
                'name' => $longName,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name'])
                ->assertJson([
                    'message' => 'Ошибка валидации',
                ]);

            // Проверяем сообщение об ошибке
            $responseData = $response->json();
            expect($responseData['errors']['name'][0])->toBe('Имя пользователя не может быть длиннее 255 символов.');
        });

        it('allows name with 255 characters', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            $longName = str_repeat('a', 255); // Ровно 255 символов

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->putJson('/api/v1/user', [
                'name' => $longName,
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Профиль успешно обновлен',
                ]);

            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'name' => $longName,
            ]);
        });

        it('requires authentication to update profile', function () {
            $response = $this->putJson('/api/v1/user', [
                'name' => 'New Name',
            ]);

            $response->assertStatus(401);
        });

        it('preserves user email when updating name', function () {
            $user = User::factory()->create([
                'name' => 'Old Name',
                'email' => 'preserve@example.com',
            ]);
            $token = $user->createToken('test-token')->plainTextToken;

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->putJson('/api/v1/user', [
                'name' => 'Updated Name',
            ]);

            $response->assertStatus(200);

            // Проверяем, что email не изменился
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'name' => 'Updated Name',
                'email' => 'preserve@example.com',
            ]);

            expect($response->json('data.email'))->toBe('preserve@example.com');
        });
    });

    describe('Password Management', function () {
        it('allows user to change password', function () {
            $user = User::factory()->create([
                'password' => Hash::make('oldpassword'),
            ]);
            $token = $user->createToken('test-token')->plainTextToken;

            $passwordData = [
                'current_password' => 'oldpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->postJson('/api/v1/change-password', $passwordData);

            $response->assertStatus(200)
                ->assertJson([
                    'data' => null,
                    'message' => 'Пароль успешно изменен',
                ]);
        });

        it('revokes other tokens when changing password but keeps the current one', function () {
            $user = User::factory()->create([
                'password' => Hash::make('oldpassword'),
            ]);
            $currentToken = $user->createToken('device-a');
            $otherToken = $user->createToken('device-b');

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$currentToken->plainTextToken,
            ])->postJson('/api/v1/change-password', [
                'current_password' => 'oldpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

            $response->assertStatus(200);

            expect(\Laravel\Sanctum\PersonalAccessToken::find($currentToken->accessToken->id))->not->toBeNull();
            expect(\Laravel\Sanctum\PersonalAccessToken::find($otherToken->accessToken->id))->toBeNull();
        });

        it('allows user to request password reset', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
            ]);

            // Mock the notification to prevent actual email sending
            Notification::fake();

            $response = $this->postJson('/api/v1/forgot-password', [
                'email' => 'test@example.com',
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'data' => null,
                    'message' => 'Письмо отправлено на test@example.com',
                ]);
        });

        it('returns the same response for a non-existent email, to avoid user enumeration', function () {
            Notification::fake();

            $response = $this->postJson('/api/v1/forgot-password', [
                'email' => 'nobody-registered@example.com',
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'data' => null,
                    'message' => 'Письмо отправлено на nobody-registered@example.com',
                ]);
        });

        it('does not reveal registration status via validation errors', function () {
            $response = $this->postJson('/api/v1/forgot-password', [
                'email' => 'nobody-registered@example.com',
            ]);

            // Раньше 'exists:users,email' возвращал 422 только для незарегистрированных
            // email — это и есть user enumeration. Теперь такой email не должен
            // отличаться от зарегистрированного никаким статусом/сообщением.
            $response->assertStatus(200);
        });
    });

    describe('Token Refresh', function () {
        it('refreshes a token that has not expired yet', function () {
            $user = User::factory()->create();
            $token = $user->createToken('auth_token')->plainTextToken;

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->postJson('/api/v1/refresh-token');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => ['user', 'token', 'token_type'],
                    'message',
                ]);
            expect($response->json('data.token'))->not->toBe($token);
        });

        it('refreshes a token that expired recently, within the grace period', function () {
            $user = User::factory()->create();
            $plainToken = $user->createToken('auth_token')->plainTextToken;
            $tokenId = explode('|', $plainToken)[0];

            \Laravel\Sanctum\PersonalAccessToken::where('id', $tokenId)
                ->update(['expires_at' => now()->subDays(3)]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$plainToken,
            ])->postJson('/api/v1/refresh-token');

            $response->assertStatus(200);
        });

        it('rejects a token that expired long ago, beyond the grace period, and deletes it', function () {
            $user = User::factory()->create();
            $plainToken = $user->createToken('auth_token')->plainTextToken;
            $tokenId = explode('|', $plainToken)[0];

            \Laravel\Sanctum\PersonalAccessToken::where('id', $tokenId)
                ->update(['expires_at' => now()->subDays(30)]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$plainToken,
            ])->postJson('/api/v1/refresh-token');

            $response->assertStatus(401);
            expect(\Laravel\Sanctum\PersonalAccessToken::where('id', $tokenId)->exists())->toBeFalse();
        });

        it('rejects an unknown token', function () {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer 999999|nonexistenttokenvalue',
            ])->postJson('/api/v1/refresh-token');

            $response->assertStatus(401);
        });
    });
});
