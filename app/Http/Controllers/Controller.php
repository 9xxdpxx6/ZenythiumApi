<?php

declare(strict_types=1);

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Zenythium Fitness API",
 *     version="1.0.0",
 *     description="API для управления тренировками, упражнениями и планами тренировок",
 *     @OA\Contact(
 *         email="support@zenythium.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Основной сервер API"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="apiKey",
 *     in="header",
 *     name="Authorization",
 *     description="Bearer токен для аутентификации. Формат: Bearer {token}"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="Аутентификация и авторизация пользователей"
 * )
 * 
 * @OA\Tag(
 *     name="Workouts",
 *     description="Управление тренировками"
 * )
 * 
 * @OA\Tag(
 *     name="Exercises",
 *     description="Управление упражнениями"
 * )
 * 
 * @OA\Tag(
 *     name="Plans",
 *     description="Управление планами тренировок"
 * )
 * 
 * @OA\Tag(
 *     name="Cycles",
 *     description="Управление циклами тренировок"
 * )
 * 
 * @OA\Tag(
 *     name="Metrics",
 *     description="Управление метриками"
 * )
 * 
 * @OA\Tag(
 *     name="Muscle Groups",
 *     description="Управление группами мышц"
 * )
 * 
 * @OA\Tag(
 *     name="Workout Sets",
 *     description="Управление подходами в тренировках"
 * )
 * 
 * @OA\Tag(
 *     name="Statistics",
 *     description="Статистика пользователя"
 * )
 */
abstract class Controller
{
    //
}
