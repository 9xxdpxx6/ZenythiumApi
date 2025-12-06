# Настройка Push-уведомлений (FCM)

Данная документация описывает процесс настройки Firebase Cloud Messaging (FCM) для отправки push-уведомлений в мобильном приложении Zenythium.

## Содержание

1. [Настройка Firebase](#настройка-firebase)
2. [Настройка мобильного приложения (Ionic + Vue + Capacitor)](#настройка-мобильного-приложения)
3. [Настройка Laravel API](#настройка-laravel-api)
4. [Тестирование](#тестирование)
5. [Альтернативы для работы из РФ](#альтернативы-для-работы-из-рф)

---

## Настройка Firebase

### 1. Создание проекта в Firebase Console

1. Перейдите на [Firebase Console](https://console.firebase.google.com/)
2. Нажмите "Добавить проект" или выберите существующий
3. Заполните название проекта (например, "Zenythium")
4. При необходимости включите Google Analytics (опционально)

### 2. Добавление Android приложения

1. В Firebase Console выберите ваш проект
2. Нажмите на иконку Android или "Добавить приложение" → Android
3. Заполните данные:
   - **Package name**: `com.zenythium.app` (должен совпадать с `appId` в `capacitor.config.ts`)
   - **App nickname**: Zenythium Android (опционально)
   - **Debug signing certificate SHA-1**: опционально (для тестирования)
4. Нажмите "Зарегистрировать приложение"
5. Скачайте файл `google-services.json`
6. Скопируйте `google-services.json` в папку `android/app/` вашего Capacitor проекта

### 3. Добавление iOS приложения

1. В Firebase Console нажмите "Добавить приложение" → iOS
2. Заполните данные:
   - **Bundle ID**: `com.zenythium.app` (должен совпадать с `appId` в `capacitor.config.ts`)
   - **App nickname**: Zenythium iOS (опционально)
3. Нажмите "Зарегистрировать приложение"
4. Скачайте файл `GoogleService-Info.plist`
5. Скопируйте `GoogleService-Info.plist` в папку `ios/App/App/` вашего Capacitor проекта

### 4. Получение учетных данных для отправки уведомлений

Firebase Cloud Messaging использует два подхода для отправки уведомлений:

#### Вариант 1: V1 API с Service Account (рекомендуется, единственный доступный для новых проектов)

> **⚠️ Важно**: Firebase отключил Legacy API для новых проектов. Server Key больше не доступен. Нужно использовать V1 API с Service Account.

V1 API - это современный и рекомендуемый способ отправки уведомлений. Он использует OAuth 2.0 и Service Account вместо Server Key.

**Шаги настройки:**

1. В Firebase Console перейдите в **Настройки проекта** (шестеренка рядом с "Обзор проекта")
2. Перейдите на вкладку **Cloud Messaging**
3. Убедитесь, что **Firebase Cloud Messaging API (V1)** включен (статус "Enabled" с зеленой галочкой)
4. Скопируйте **Sender ID** из раздела **Firebase Cloud Messaging API (V1)** (отображается в сером блоке, например: `733394115472`)
5. Скопируйте **Project ID** (можно найти в настройках проекта → вкладка **General** → **Project ID**)
6. Перейдите на вкладку **Service accounts**
7. Убедитесь, что выбран правильный проект Firebase (если нет - выберите в выпадающем списке вверху)
8. Нажмите кнопку **"Generate new private key"** (или **"Manage service accounts"** → откроется Google Cloud Console)
9. В открывшемся окне/диалоге:
   - Если открылся диалог в Firebase Console: нажмите **"Generate key"** → файл автоматически скачается
   - Если открылся Google Cloud Console:
     - Выберите Service Account (обычно `firebase-adminsdk-xxxxx@your-project.iam.gserviceaccount.com`)
     - Перейдите на вкладку **"Keys"**
     - Нажмите **"Add Key"** → **"Create new key"**
     - Выберите формат **JSON**
     - Нажмите **"Create"** - файл автоматически скачается
10. Сохраните JSON файл в безопасном месте (например, `storage/app/private/firebase-service-account.json`)
11. **НЕ коммитьте** этот файл в репозиторий! Файлы в `storage/app/private/` уже игнорируются `.gitignore`

> **Важно**: 
> - JSON файл содержит секретные ключи - храните его в безопасности
> - Sender ID можно скопировать из раздела Cloud Messaging (отображается в разделе V1 API в сером блоке)
> - Project ID можно найти в настройках проекта → вкладка General

#### Вариант 2: Legacy API с Server Key (устаревший, но проще)

> **⚠️ Внимание**: Legacy API deprecated и будет отключен 20 июня 2024 года. Используйте только если V1 API недоступен.

Если вы видите раздел **"Cloud Messaging API (Legacy)"** с статусом **"Disabled"**:

1. Нажмите на три точки (⋮) справа от названия раздела
2. Выберите **"Enable"** (если эта опция доступна)
3. После включения в этом разделе появится **Server key**
4. Скопируйте **Server key**
5. Скопируйте **Sender ID** (он одинаковый для обоих API)

> **Примечание**: Если опция включения Legacy API недоступна, это означает, что Firebase полностью отключил Legacy API для новых проектов. В этом случае используйте только V1 API.

### 5. Включение Cloud Messaging API

1. Перейдите в [Google Cloud Console](https://console.cloud.google.com/)
2. Выберите ваш Firebase проект
3. Перейдите в **APIs & Services** → **Library**
4. Найдите **Firebase Cloud Messaging API**
5. Убедитесь, что API включен (если нет - нажмите "Enable")

---

## Настройка мобильного приложения

### 1. Установка необходимых пакетов

```bash
npm install @capacitor/push-notifications
npm install @capacitor-community/fcm
```

### 2. Синхронизация Capacitor

```bash
npx cap sync
```

### 3. Настройка Android

#### 3.1. Обновление `android/app/build.gradle`

Убедитесь, что в файле `android/app/build.gradle` добавлен Google Services plugin:

```gradle
apply plugin: 'com.google.gms.google-services'

dependencies {
    // ... другие зависимости
    implementation platform('com.google.firebase:firebase-bom:32.7.0')
    implementation 'com.google.firebase:firebase-messaging'
}
```

#### 3.2. Обновление `android/build.gradle`

В корневом `android/build.gradle` добавьте:

```gradle
buildscript {
    dependencies {
        classpath 'com.google.gms:google-services:4.4.0'
    }
}
```

#### 3.3. Разрешения в `AndroidManifest.xml`

Убедитесь, что в `android/app/src/main/AndroidManifest.xml` есть разрешения:

```xml
<uses-permission android:name="android.permission.INTERNET"/>
<uses-permission android:name="android.permission.POST_NOTIFICATIONS"/>
```

### 4. Настройка iOS

#### 4.1. Обновление `ios/App/Podfile`

Убедитесь, что в `Podfile` добавлен Firebase:

```ruby
platform :ios, '13.0'
use_frameworks!

target 'App' do
  # ... другие зависимости
  pod 'Firebase/Messaging'
end
```

Затем выполните:

```bash
cd ios/App
pod install
```

#### 4.2. Включение Push Notifications в Xcode

1. Откройте проект в Xcode: `npx cap open ios`
2. Выберите проект в навигаторе
3. Перейдите на вкладку **Signing & Capabilities**
4. Нажмите **+ Capability**
5. Добавьте **Push Notifications**
6. Добавьте **Background Modes** и включите **Remote notifications**

#### 4.3. Настройка APNs (Apple Push Notification service)

1. В [Apple Developer Portal](https://developer.apple.com/) создайте APNs ключ:
   - Certificates, Identifiers & Profiles → Keys
   - Создайте новый ключ с включенным **Apple Push Notifications service (APNs)**
   - Скачайте `.p8` файл (сохраните Key ID)
2. В Firebase Console:
   - Настройки проекта → Cloud Messaging → Apple app configuration
   - Загрузите APNs Authentication Key (`.p8` файл)
   - Введите Key ID и Team ID

### 5. Реализация в Vue компоненте

Создайте сервис для работы с push-уведомлениями:

```typescript
// src/services/push-notifications.ts
import { PushNotifications } from '@capacitor/push-notifications';
import { FCM } from '@capacitor-community/fcm';
import { Capacitor } from '@capacitor/core';

export class PushNotificationService {
  private static instance: PushNotificationService;
  private fcmToken: string | null = null;

  static getInstance(): PushNotificationService {
    if (!PushNotificationService.instance) {
      PushNotificationService.instance = new PushNotificationService();
    }
    return PushNotificationService.instance;
  }

  async initialize(): Promise<void> {
    if (!Capacitor.isNativePlatform()) {
      console.log('Push notifications only work on native platforms');
      return;
    }

    // Запрашиваем разрешение
    let permStatus = await PushNotifications.checkPermissions();

    if (permStatus.receive === 'prompt') {
      permStatus = await PushNotifications.requestPermissions();
    }

    if (permStatus.receive !== 'granted') {
      throw new Error('User denied permissions!');
    }

    // Регистрируем для получения уведомлений
    await PushNotifications.register();

    // Получаем FCM токен
    await this.getFCMToken();
  }

  private async getFCMToken(): Promise<void> {
    try {
      const token = await FCM.getToken();
      this.fcmToken = token.token;
      console.log('FCM Token:', token.token);
      
      // Отправляем токен на сервер
      await this.sendTokenToServer(token.token);
    } catch (error) {
      console.error('Error getting FCM token:', error);
    }
  }

  private async sendTokenToServer(token: string): Promise<void> {
    try {
      const platform = Capacitor.getPlatform();
      const response = await fetch(`${import.meta.env.VITE_API_URL}/api/v1/user/device-tokens`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        },
        body: JSON.stringify({
          device_token: token,
          platform: platform === 'ios' ? 'ios' : 'android',
        }),
      });

      if (!response.ok) {
        throw new Error('Failed to send token to server');
      }

      console.log('Token sent to server successfully');
    } catch (error) {
      console.error('Error sending token to server:', error);
    }
  }

  async onNotificationReceived(callback: (notification: any) => void): Promise<void> {
    PushNotifications.addListener('pushNotificationReceived', (notification) => {
      console.log('Push notification received:', notification);
      callback(notification);
    });
  }

  async onNotificationActionPerformed(callback: (action: any) => void): Promise<void> {
    PushNotifications.addListener('pushNotificationActionPerformed', (action) => {
      console.log('Push notification action performed:', action);
      callback(action);
    });
  }

  async onTokenRefresh(callback: (token: any) => void): Promise<void> {
    PushNotifications.addListener('registration', (token) => {
      console.log('Push registration success, token: ' + token.value);
      callback(token);
    });

    FCM.onTokenRefresh(async (token) => {
      console.log('FCM token refreshed:', token.token);
      this.fcmToken = token.token;
      await this.sendTokenToServer(token.token);
      callback(token);
    });
  }

  getToken(): string | null {
    return this.fcmToken;
  }
}
```

### 6. Использование в главном компоненте приложения

```typescript
// src/App.vue или main.ts
import { onMounted } from 'vue';
import { PushNotificationService } from './services/push-notifications';

export default {
  setup() {
    onMounted(async () => {
      try {
        const pushService = PushNotificationService.getInstance();
        await pushService.initialize();

        // Обработка получения уведомления
        await pushService.onNotificationReceived((notification) => {
          console.log('Notification received:', notification);
          // Показать уведомление пользователю
        });

        // Обработка нажатия на уведомление
        await pushService.onNotificationActionPerformed((action) => {
          console.log('Notification action:', action);
          // Навигация на нужный экран
        });

        // Обработка обновления токена
        await pushService.onTokenRefresh((token) => {
          console.log('Token refreshed:', token);
        });
      } catch (error) {
        console.error('Push notifications initialization error:', error);
      }
    });
  },
};
```

---

## Настройка Laravel API

### 1. Переменные окружения

#### Если используете V1 API (рекомендуется):

Добавьте в файл `.env`:

```env
FCM_USE_V1_API=true
FCM_SERVICE_ACCOUNT_PATH=storage/app/private/firebase-service-account.json
FCM_SENDER_ID=your_firebase_sender_id_here
FCM_PROJECT_ID=your_firebase_project_id_here
```

> **Важно**: 
> - `FCM_SERVICE_ACCOUNT_PATH` - путь к JSON файлу Service Account (относительно корня проекта). Рекомендуется использовать `storage/app/private/` для приватных файлов
> - `FCM_SENDER_ID` - скопируйте из Firebase Console → Cloud Messaging → Firebase Cloud Messaging API (V1) (отображается в сером блоке)
> - `FCM_PROJECT_ID` - ID вашего Firebase проекта (можно найти в настройках проекта → General)

#### Если используете Legacy API (устаревший):

Добавьте в файл `.env`:

```env
FCM_USE_V1_API=false
FCM_SERVER_KEY=your_firebase_server_key_here
FCM_SENDER_ID=your_firebase_sender_id_here
```

> **Важно**: Замените значения на реальные из Firebase Console.

### 2. Проверка конфигурации

Убедитесь, что в `config/services.php` есть настройки FCM:

```php
'fcm' => [
    'use_v1_api' => env('FCM_USE_V1_API', true),
    'server_key' => env('FCM_SERVER_KEY'),
    'sender_id' => env('FCM_SENDER_ID'),
    'project_id' => env('FCM_PROJECT_ID'),
    'service_account_path' => env('FCM_SERVICE_ACCOUNT_PATH', storage_path('app/private/firebase-service-account.json')),
],
```

### 3. Регистрация FCM канала

FCM канал уже зарегистрирован в `app/Providers/AppServiceProvider.php`:

```php
$this->app->make(ChannelManager::class)->extend('fcm', function () {
    return new FcmChannel();
});
```

### 4. Миграции

Убедитесь, что выполнены миграции:

```bash
php artisan migrate
```

Это создаст таблицу `user_device_tokens` для хранения токенов устройств.

### 5. API Endpoints

Система уже включает следующие endpoints:

- `POST /api/v1/user/device-tokens` - регистрация токена устройства
- `DELETE /api/v1/user/device-tokens/{id}` - удаление токена устройства

### 6. Тестирование отправки уведомлений

Создайте тестовый endpoint для проверки (только для разработки):

```php
// routes/api.php (только для разработки!)
Route::post('/test-push', function (Request $request) {
    $user = $request->user();
    $user->notify(new \App\Notifications\GoalAchievedNotification(
        \App\Models\Goal::first()
    ));
    return response()->json(['message' => 'Push notification sent']);
})->middleware('auth:sanctum');
```

---

## Тестирование

### 1. Тестирование на Android

1. Соберите APK:
   ```bash
   npx cap build android
   ```
2. Установите на устройство или эмулятор
3. Проверьте логи:
   ```bash
   npx cap run android
   adb logcat | grep -i fcm
   ```
4. Отправьте тестовое уведомление через Firebase Console:
   - Firebase Console → Cloud Messaging → New notification
   - Выберите ваше приложение
   - Введите заголовок и текст
   - Отправьте на тестовое устройство

### 2. Тестирование на iOS

1. Откройте проект в Xcode:
   ```bash
   npx cap open ios
   ```
2. Выберите устройство и запустите приложение
3. Проверьте логи в Xcode Console
4. Отправьте тестовое уведомление через Firebase Console

### 3. Проверка регистрации токена

1. Откройте приложение
2. Проверьте в логах наличие FCM токена
3. Проверьте в базе данных таблицу `user_device_tokens`:
   ```sql
   SELECT * FROM user_device_tokens WHERE user_id = YOUR_USER_ID;
   ```

### 4. Тестирование отправки с сервера

Используйте тестовый endpoint или создайте цель, которая автоматически отправит уведомление при достижении.

---

## Альтернативы для работы из РФ

### Проблема с доступностью FCM в России

Firebase Cloud Messaging может быть недоступен или нестабилен из России из-за блокировок. Рассмотрите следующие альтернативы:

### 1. RuStore Push Notifications

**Преимущества:**
- Работает из РФ
- API совместимо с FCM
- Поддержка Android и iOS

**Настройка:**
1. Зарегистрируйтесь в [RuStore](https://www.rustore.ru/)
2. Создайте приложение
3. Получите API ключ
4. Замените FCM на RuStore в `FcmChannel.php`

### 2. Собственный сервер push-уведомлений

**Для iOS:**
- Используйте APNs напрямую через HTTP/2 API
- Требуется сертификат или ключ APNs

**Для Android:**
- Используйте WebSocket соединение
- Или собственный сервер с постоянным соединением

### 3. Гибридный подход

- Основной канал: RuStore Push
- Резервный: FCM (если доступен)
- Fallback: Email уведомления

### Настройка RuStore Push (если выбран этот вариант)

1. Обновите `config/services.php`:
   ```php
   'push' => [
       'provider' => env('PUSH_PROVIDER', 'rustore'),
       'rustore' => [
           'api_key' => env('RUSTORE_API_KEY'),
           'api_url' => env('RUSTORE_API_URL', 'https://push.rustore.ru/api'),
       ],
   ],
   ```

2. Обновите `FcmChannel.php` для поддержки RuStore

---

## Устранение неполадок

### Проблема: Токен не регистрируется

**Решение:**
- Проверьте, что `google-services.json` (Android) или `GoogleService-Info.plist` (iOS) находятся в правильных папках
- Убедитесь, что Package Name / Bundle ID совпадают в Firebase и приложении
- Проверьте логи на наличие ошибок

### Проблема: Уведомления не приходят

**Решение:**
- Проверьте, что устройство подключено к интернету
- Убедитесь, что приложение имеет разрешение на уведомления
- Проверьте, что токен сохранен в базе данных
- Проверьте логи сервера на наличие ошибок отправки

### Проблема: Ошибка 401 при отправке

**Решение:**
- Если используете V1 API:
  - Проверьте, что путь к Service Account файлу правильный
  - Убедитесь, что файл Service Account существует и валиден
  - Проверьте, что `FCM_PROJECT_ID` правильный
- Если используете Legacy API:
  - Проверьте, что `FCM_SERVER_KEY` в `.env` правильный
  - Убедитесь, что Server Key не истек (в Firebase Console можно пересоздать)

### Проблема: Server Key не виден в Firebase Console

**Решение:**
- Это нормально для новых проектов Firebase - Legacy API отключен
- Используйте V1 API с Service Account (см. раздел "Получение учетных данных")
- Если Legacy API отключен, опция включения может быть недоступна

### Проблема: Уведомления приходят, но не отображаются

**Решение:**
- Проверьте настройки уведомлений в системе устройства
- Убедитесь, что приложение имеет разрешение на показ уведомлений
- Проверьте, что обработчики уведомлений правильно настроены в коде

---

## Дополнительные ресурсы

- [Firebase Cloud Messaging Documentation](https://firebase.google.com/docs/cloud-messaging)
- [Capacitor Push Notifications Plugin](https://capacitorjs.com/docs/apis/push-notifications)
- [FCM HTTP v1 API](https://firebase.google.com/docs/cloud-messaging/migrate-v1)
- [RuStore Push Notifications](https://www.rustore.ru/help/sdk/push-notifications)

---

## Безопасность

⚠️ **Важные моменты безопасности:**

1. **Никогда не коммитьте** `FCM_SERVER_KEY` в публичный репозиторий
2. Используйте переменные окружения для всех секретных ключей
3. Ограничьте доступ к endpoints регистрации токенов (только для аутентифицированных пользователей)
4. Регулярно обновляйте Firebase SDK и зависимости
5. Мониторьте использование API ключей на предмет подозрительной активности

---

## Поддержка

При возникновении проблем:
1. Проверьте логи приложения и сервера
2. Убедитесь, что все шаги настройки выполнены
3. Проверьте документацию Firebase и Capacitor
4. Обратитесь к команде разработки

