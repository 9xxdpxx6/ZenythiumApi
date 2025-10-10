<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

describe('Authentication', function () {
    describe('Registration', function () {
        it('allows user to register', function () {
            $userData = [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
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
                'Authorization' => 'Bearer ' . $token,
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
                'Authorization' => 'Bearer ' . $token,
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
                    'message'
                ]);
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
                'Authorization' => 'Bearer ' . $token,
            ])->postJson('/api/v1/change-password', $passwordData);

            $response->assertStatus(200)
                ->assertJson([
                    'data' => null,
                    'message' => 'Пароль успешно изменен',
                ]);
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
                    'message' => 'Ссылка для сброса пароля отправлена на вашу почту',
                ]);
        });
    });
});