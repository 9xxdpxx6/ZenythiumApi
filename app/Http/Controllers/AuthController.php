<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Services\SmartCaptchaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthController extends Controller
{
    public function __construct(
        private readonly SmartCaptchaService $smartCaptchaService
    ) {}
    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     summary="Регистрация нового пользователя",
     *     description="Создает нового пользователя в системе и возвращает токен доступа для последующей аутентификации",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation","smartcaptcha_token"},
     *             @OA\Property(property="name", type="string", example="Иван Петров", description="Имя пользователя"),
     *             @OA\Property(property="email", type="string", format="email", example="ivan@example.com", description="Email пользователя (должен быть уникальным)"),
     *             @OA\Property(property="password", type="string", format="password", example="SecurePass123", description="Пароль (минимум 8 символов)"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="SecurePass123", description="Подтверждение пароля"),
     *             @OA\Property(property="smartcaptcha_token", type="string", example="smartcaptcha_token_here", description="Токен от Yandex SmartCaptcha")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Пользователь успешно зарегистрирован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Пользователь успешно зарегистрирован"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Иван Петров"),
     *                 @OA\Property(property="email", type="string", example="ivan@example.com")
     *             ),
     *             @OA\Property(property="token", type="string", example="1|abc123def456..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ошибка валидации"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string"), example={"Пользователь с таким email уже зарегистрирован."}),
     *                 @OA\Property(property="name", type="array", @OA\Items(type="string"), example={"Имя пользователя обязательно."}),
     *                 @OA\Property(property="password", type="array", @OA\Items(type="string"), example={"Пароль должен содержать минимум 8 символов."})
     *             )
     *         )
     *     )
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Проверяем капчу перед регистрацией
        $ip = $request->ip();
        $token = $request->input('smartcaptcha_token');
        
        if (!$this->smartCaptchaService->verify($token, $ip)) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => [
                    'smartcaptcha_token' => ['Не удалось проверить капчу. Попробуйте еще раз.'],
                ],
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Пользователь успешно зарегистрирован',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toISOString(),
                'updated_at' => $user->updated_at?->toISOString(),
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     summary="Вход пользователя в систему",
     *     description="Аутентифицирует пользователя по email и паролю, возвращает токен доступа",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="ivan@example.com", description="Email пользователя"),
     *             @OA\Property(property="password", type="string", format="password", example="SecurePass123", description="Пароль пользователя")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Вход выполнен успешно",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Вход выполнен успешно"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Иван Петров"),
     *                 @OA\Property(property="email", type="string", example="ivan@example.com")
     *             ),
     *             @OA\Property(property="token", type="string", example="1|abc123def456..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Неверные учетные данные",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Неверные учетные данные")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ошибка валидации"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string"), example={"Поле email обязательно"})
     *             )
     *         )
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Неверные учетные данные',
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Вход выполнен успешно',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toISOString(),
                'updated_at' => $user->updated_at?->toISOString(),
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/logout",
     *     summary="Выход пользователя из системы",
     *     description="Удаляет текущий токен доступа пользователя, завершая сессию",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Выход выполнен успешно",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="message", type="string", example="Выход выполнен успешно")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        // Проверяем, является ли токен PersonalAccessToken (token-based) или TransientToken (cookie-based)
        if ($token instanceof PersonalAccessToken) {
            // Для token-based аутентификации удаляем токен из БД
            $token->delete();
        }
        // Для cookie-based аутентификации (TransientToken) просто завершаем сессию
        // Cookie будет очищен автоматически при следующем запросе или можно использовать Auth::logout()

        return response()->json([
            'data' => null,
            'message' => 'Выход выполнен успешно'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/logout-all",
     *     summary="Выход со всех устройств",
     *     description="Удаляет все токены доступа пользователя, завершая все активные сессии",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Выход со всех устройств выполнен",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="message", type="string", example="Выход со всех устройств выполнен")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Удаляем только PersonalAccessToken (token-based)
        // Для cookie-based аутентификации токены не хранятся в БД
        $user->tokens()->delete();

        return response()->json([
            'data' => null,
            'message' => 'Выход со всех устройств выполнен'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user",
     *     summary="Получение данных текущего пользователя",
     *     description="Возвращает информацию о текущем аутентифицированном пользователе",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Данные пользователя успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Иван Петров"),
     *                 @OA\Property(property="email", type="string", example="ivan@example.com"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
     *             ),
     *             @OA\Property(property="message", type="string", example="Данные пользователя успешно получены")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'created_at' => $request->user()->created_at?->toISOString(),
                'updated_at' => $request->user()->updated_at?->toISOString(),
            ],
            'message' => 'Данные пользователя успешно получены'
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/user",
     *     summary="Обновление профиля пользователя",
     *     description="Обновляет данные профиля текущего аутентифицированного пользователя (никнейм)",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Новый никнейм", description="Новый никнейм пользователя (до 255 символов)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Профиль успешно обновлен",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Новый никнейм"),
     *                 @OA\Property(property="email", type="string", example="ivan@example.com"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
     *             ),
     *             @OA\Property(property="message", type="string", example="Профиль успешно обновлен")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ошибка валидации"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="name", type="array", @OA\Items(type="string"), example={"Имя пользователя обязательно."})
     *             )
     *         )
     *     )
     * )
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        
        $user->update([
            'name' => $request->name,
        ]);

        // Обновляем модель из базы данных для получения актуального updated_at
        $user->refresh();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toISOString(),
                'updated_at' => $user->updated_at?->toISOString(),
            ],
            'message' => 'Профиль успешно обновлен'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/forgot-password",
     *     summary="Отправка ссылки для сброса пароля",
     *     description="Отправляет email с ссылкой для сброса пароля на указанный адрес",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="ivan@example.com", description="Email пользователя")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ссылка для сброса пароля отправлена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="message", type="string", example="Ссылка для сброса пароля отправлена на вашу почту")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ошибка валидации"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string"), example={"Поле email обязательно"})
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка отправки email",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="message", type="string", example="Не удалось отправить ссылку для сброса пароля")
     *         )
     *     )
     * )
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'Email обязателен для заполнения.',
            'email.email' => 'Введите корректный email адрес.',
            'email.exists' => 'Пользователь с таким email не найден в системе.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $status = Password::sendResetLink($request->only('email'));

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'data' => null,
                    'message' => 'Ссылка для сброса пароля отправлена на вашу почту'
                ]);
            }

            // Обработка других статусов
            $message = match($status) {
                Password::RESET_THROTTLED => 'Слишком много попыток. Пожалуйста, попробуйте позже.',
                Password::INVALID_USER => 'Пользователь с таким email не найден.',
                default => 'Не удалось отправить ссылку для сброса пароля. Статус: ' . $status,
            };

            return response()->json([
                'data' => null,
                'message' => $message
            ], $status === Password::RESET_THROTTLED ? 429 : 500);
        } catch (\Exception $e) {
            // Логируем ошибку для отладки
            Log::error('Password reset error: ' . $e->getMessage(), [
                'email' => $request->email,
                'exception' => $e
            ]);

            return response()->json([
                'data' => null,
                'message' => 'Не удалось отправить ссылку для сброса пароля. Проверьте настройки почты.'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reset-password",
     *     summary="Сброс пароля",
     *     description="Сбрасывает пароль пользователя с использованием токена из email",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token","email","password","password_confirmation"},
     *             @OA\Property(property="token", type="string", example="abc123def456", description="Токен сброса пароля из email"),
     *             @OA\Property(property="email", type="string", format="email", example="ivan@example.com", description="Email пользователя"),
     *             @OA\Property(property="password", type="string", format="password", example="NewSecurePass123", description="Новый пароль (минимум 8 символов)"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="NewSecurePass123", description="Подтверждение нового пароля")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Пароль успешно сброшен",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="message", type="string", example="Пароль успешно сброшен")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ошибка валидации"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сброса пароля",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="message", type="string", example="Не удалось сбросить пароль")
     *         )
     *     )
     * )
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'data' => null,
                'message' => 'Пароль успешно сброшен'
            ]);
        }

        return response()->json([
            'data' => null,
            'message' => 'Не удалось сбросить пароль'
        ], 500);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/change-password",
     *     summary="Изменение пароля",
     *     description="Изменяет пароль текущего аутентифицированного пользователя",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password","password","password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password", example="OldPassword123", description="Текущий пароль"),
     *             @OA\Property(property="password", type="string", format="password", example="NewPassword123", description="Новый пароль (минимум 8 символов)"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="NewPassword123", description="Подтверждение нового пароля")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Пароль успешно изменен",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="message", type="string", example="Пароль успешно изменен")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ошибка валидации"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="current_password", type="array", @OA\Items(type="string"), example={"Текущий пароль неверен"})
     *             )
     *         )
     *     )
     * )
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Текущий пароль неверен',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'data' => null,
            'message' => 'Пароль успешно изменен'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/refresh-token",
     *     summary="Обновление токена доступа",
     *     description="Удаляет текущий токен (даже просроченный) и создает новый для продления сессии. Работает без аутентификации, принимает токен в заголовке Authorization",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=false,
     *         description="Токен передается в заголовке Authorization: Bearer {token}"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Токен успешно обновлен",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Иван Петров"),
     *                     @OA\Property(property="email", type="string", example="ivan@example.com")
     *                 ),
     *                 @OA\Property(property="token", type="string", example="2|xyz789abc123..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
     *             ),
     *             @OA\Property(property="message", type="string", example="Токен успешно обновлен")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Неверный или отсутствующий токен",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Токен не предоставлен")
     *         )
     *     )
     * )
     */
    public function refreshToken(Request $request): JsonResponse
    {
        // Получаем токен из заголовка Authorization
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'message' => 'Токен не предоставлен',
            ], 401);
        }
        
        // Находим токен в БД (даже если он просрочен)
        // Используем findToken(), который может не найти просроченный токен,
        // поэтому также ищем напрямую через хеш
        $accessToken = PersonalAccessToken::findToken($token);
        
        // Если findToken() не нашел токен (возможно, из-за просрочки),
        // ищем напрямую через хеш в БД
        if (!$accessToken) {
            // Разбираем токен: формат "id|hash"
            $parts = explode('|', $token, 2);
            
            if (count($parts) !== 2) {
                return response()->json([
                    'message' => 'Неверный формат токена',
                ], 401);
            }
            
            [$id, $tokenValue] = $parts;
            
            // Вычисляем хеш токена (как это делает Sanctum)
            $hash = hash('sha256', $tokenValue);
            
            // Ищем токен в БД напрямую, игнорируя проверку срока действия
            $accessToken = PersonalAccessToken::where('id', $id)
                ->where('token', $hash)
                ->first();
        }
        
        if (!$accessToken) {
            return response()->json([
                'message' => 'Неверный токен',
            ], 401);
        }
        
        // Получаем пользователя из токена
        $user = $accessToken->tokenable;
        
        if (!$user) {
            return response()->json([
                'message' => 'Пользователь не найден',
            ], 401);
        }
        
        // Удаляем старый токен (даже если он просрочен)
        $accessToken->delete();
        
        // Создаем новый токен
        $newToken = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $newToken,
                'token_type' => 'Bearer'
            ],
            'message' => 'Токен успешно обновлен'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/user/device-tokens",
     *     summary="Регистрация токена устройства для push-уведомлений",
     *     description="Регистрирует FCM токен устройства пользователя для отправки push-уведомлений",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"device_token", "platform"},
     *             @OA\Property(property="device_token", type="string", example="fcm_token_here", description="FCM токен устройства"),
     *             @OA\Property(property="platform", type="string", enum={"ios", "android"}, example="android", description="Платформа устройства"),
     *             @OA\Property(property="device_id", type="string", nullable=true, example="device_unique_id", description="Уникальный ID устройства (опционально)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Токен устройства успешно зарегистрирован",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="device_token", type="string", example="fcm_token_here"),
     *                 @OA\Property(property="platform", type="string", example="android")
     *             ),
     *             @OA\Property(property="message", type="string", example="Токен устройства успешно зарегистрирован")
     *         )
     *     )
     * )
     */
    public function registerDeviceToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string',
            'platform' => 'required|string|in:ios,android',
            'device_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        
        // Проверяем, существует ли уже такой токен
        $existingToken = UserDeviceToken::where('device_token', $request->device_token)->first();
        
        if ($existingToken) {
            // Если токен принадлежит другому пользователю, обновляем его
            if ($existingToken->user_id !== $user->id) {
                $existingToken->update([
                    'user_id' => $user->id,
                    'platform' => $request->platform,
                    'device_id' => $request->device_id,
                ]);
                $deviceToken = $existingToken;
            } else {
                // Если токен уже принадлежит этому пользователю, просто обновляем данные
                $existingToken->update([
                    'platform' => $request->platform,
                    'device_id' => $request->device_id,
                ]);
                $deviceToken = $existingToken;
            }
        } else {
            // Создаем новый токен
            $deviceToken = UserDeviceToken::create([
                'user_id' => $user->id,
                'device_token' => $request->device_token,
                'platform' => $request->platform,
                'device_id' => $request->device_id,
            ]);
        }

        return response()->json([
            'data' => [
                'id' => $deviceToken->id,
                'device_token' => substr($deviceToken->device_token, 0, 20) . '...',
                'platform' => $deviceToken->platform,
            ],
            'message' => 'Токен устройства успешно зарегистрирован',
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/user/device-tokens/{id}",
     *     summary="Удаление токена устройства",
     *     description="Удаляет токен устройства пользователя",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Токен устройства успешно удален",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Токен устройства успешно удален")
     *         )
     *     )
     * )
     */
    public function removeDeviceToken(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        
        $deviceToken = UserDeviceToken::where('user_id', $user->id)
            ->find($id);

        if (!$deviceToken) {
            return response()->json([
                'message' => 'Токен устройства не найден',
            ], 404);
        }

        $deviceToken->delete();

        return response()->json([
            'message' => 'Токен устройства успешно удален',
        ]);
    }
}