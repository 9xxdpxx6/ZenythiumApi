# Функциональность exercise_ids в планах тренировок

## Обзор

Добавлена возможность передавать массив ID упражнений (`exercise_ids`) при создании и обновлении планов тренировок. Упражнения автоматически добавляются в план в указанном порядке через таблицу `plan_exercises`.

## API Endpoints

### POST /api/v1/plans

Создание нового плана с упражнениями.

**Параметры:**
- `name` (обязательно) - название плана
- `cycle_id` (необязательно) - ID цикла тренировок
- `order` (необязательно) - порядок плана
- `is_active` (необязательно) - статус активности
- `exercise_ids` (необязательно) - массив ID упражнений

**Пример запроса:**
```json
{
  "name": "Силовая тренировка",
  "cycle_id": 1,
  "order": 1,
  "is_active": true,
  "exercise_ids": [32, 16, 8]
}
```

### PUT /api/v1/plans/{id}

Обновление плана с синхронизацией упражнений.

**Параметры:**
- Все параметры как в POST
- `exercise_ids` - при передаче все существующие упражнения удаляются и заменяются новыми

**Пример запроса:**
```json
{
  "name": "Обновленная тренировка",
  "exercise_ids": [16, 32]
}
```

## Логика работы

### Для планов с циклом (cycle_id !== null)
- Проверяется принадлежность упражнений пользователю через `exercise.user_id === plan.cycle.user_id`
- Упражнения добавляются только если принадлежат пользователю

### Для standalone планов (cycle_id === null)
- Упражнения добавляются без проверки принадлежности
- Это позволяет использовать любые упражнения в standalone планах

## Валидация

### Правила валидации для exercise_ids:
- `exercise_ids` - массив (необязательно)
- `exercise_ids.*` - целое число
- `exercise_ids.*` - упражнение должно существовать в базе данных

### Сообщения об ошибках:
- `exercise_ids.array` - "Упражнения должны быть массивом."
- `exercise_ids.*.integer` - "ID упражнения должен быть числом."
- `exercise_ids.*.exists` - "Упражнение не найдено."

## Порядок упражнений

Упражнения добавляются в том порядке, в котором указаны в массиве `exercise_ids`. Порядок сохраняется в поле `order` таблицы `plan_exercises`:

```php
// exercise_ids: [32, 16, 8]
// Результат в plan_exercises:
// exercise_id: 32, order: 1
// exercise_id: 16, order: 2  
// exercise_id: 8, order: 3
```

## Безопасность

### Планы с циклом
- Проверяется принадлежность упражнений пользователю
- Пользователь может добавлять только свои упражнения

### Standalone планы
- Проверка принадлежности отключена
- Можно добавлять любые существующие упражнения

## Тестирование

Функциональность полностью покрыта тестами:

### Unit тесты
- `PlanRequestTest.php` - валидация поля `exercise_ids`
- `PlanServiceTest.php` - методы `create` и `update` с `exercise_ids`

### Интеграционные тесты
- `PlanWithExercisesTest.php` - API endpoints с `exercise_ids`

### Запуск тестов
```bash
# Все тесты планов
php artisan test --filter=Plan

# Только тесты exercise_ids
php artisan test tests/Feature/PlanWithExercisesTest.php
php artisan test tests/Unit/PlanRequestTest.php --filter="exercise_ids"
php artisan test tests/Unit/PlanServiceTest.php --filter="exercise_ids"
```

## Примеры использования

### Создание плана с упражнениями
```javascript
const response = await fetch('/api/v1/plans', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + token
  },
  body: JSON.stringify({
    name: 'Моя тренировка',
    exercise_ids: [1, 2, 3]
  })
});
```

### Обновление упражнений плана
```javascript
const response = await fetch('/api/v1/plans/1', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + token
  },
  body: JSON.stringify({
    exercise_ids: [3, 1, 2] // Изменяем порядок
  })
});
```

### Удаление всех упражнений
```javascript
const response = await fetch('/api/v1/plans/1', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + token
  },
  body: JSON.stringify({
    exercise_ids: [] // Пустой массив удаляет все упражнения
  })
});
```
