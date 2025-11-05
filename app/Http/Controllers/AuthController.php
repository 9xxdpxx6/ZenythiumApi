<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     summary="Регистрация нового пользователя",
     *     description="Создает нового пользователя в системе и возвращает токен доступа для последующей аутентификации",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="Иван Петров", description="Имя пользователя"),
     *             @OA\Property(property="email", type="string", format="email", example="ivan@example.com", description="Email пользователя (должен быть уникальным)"),
     *             @OA\Property(property="password", type="string", format="password", example="SecurePass123", description="Пароль (минимум 8 символов)"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="SecurePass123", description="Подтверждение пароля")
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
        $request->user()->currentAccessToken()->delete();

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
        $request->user()->tokens()->delete();

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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'data' => null,
                'message' => 'Ссылка для сброса пароля отправлена на вашу почту'
            ]);
        }

        return response()->json([
            'data' => null,
            'message' => 'Не удалось отправить ссылку для сброса пароля'
        ], 500);
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
     *     description="Удаляет текущий токен и создает новый для продления сессии",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
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
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Delete current token
        $request->user()->currentAccessToken()->delete();
        
        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ],
            'message' => 'Токен успешно обновлен'
        ]);
    }
}